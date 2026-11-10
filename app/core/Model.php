<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo Base com Abstração PDO e Prepared Statements Estritos
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

require_once __DIR__ . '/Sanitizer.php';

abstract class Model
{
    /**
     * Instância única compartilhada da conexão PDO (Singleton)
     */
    protected static ?\PDO $pdo = null;

    /**
     * Nome da tabela associada ao modelo
     */
    protected string $table = '';

    /**
     * Chave primária da tabela
     */
    protected string $primaryKey = 'id';

    /**
     * Indica se a tabela possui controle de data de criação e atualização (created_at, updated_at)
     */
    protected bool $timestamps = true;

    /**
     * Indica se a tabela utiliza exclusão lógica (deleted_at)
     */
    protected bool $softDelete = true;

    /**
     * Cache em memória para validação defensiva de IDs de usuário (evita queries repetidas na mesma requisição)
     * @var array<int, bool>
     */
    private static array $validUserIdsCache = [];
    private static ?int $fallbackActiveUserId = null;

    /**
     * Obtém ou inicializa a conexão com o banco de dados MySQL via PDO
     */
    public static function getConnection(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $databaseFactory = __DIR__ . '/../../config/database.php';
        if (file_exists($databaseFactory)) {
            $conn = require $databaseFactory;
            if ($conn instanceof \PDO) {
                self::$pdo = $conn;
                return self::$pdo;
            }
        }

        if (class_exists('Database')) {
            self::$pdo = \Database::getInstance();
            return self::$pdo;
        }

        throw new \RuntimeException("Fábrica de banco de dados [config/database.php] não encontrada.");
    }

    /**
     * Alias para getConnection()
     */
    public static function getPdo(): \PDO
    {
        return self::getConnection();
    }

    /**
     * Define manualmente a instância PDO (útil para testes unitários e mocks)
     */
    public static function setConnection(?\PDO $pdo): void
    {
        self::$pdo = $pdo;
        self::$validUserIdsCache = [];
        self::$fallbackActiveUserId = null;
        if (class_exists('Database') && $pdo !== null) {
            \Database::setInstance($pdo);
        }
    }

    /**
     * Valida defensivamente se o ID de usuário existe na tabela usuarios para evitar violação de Foreign Key.
     *
     * @param int|null $usuarioId ID do usuário informado
     * @param bool $obrigatorio Se true (ex: logs_auditoria / estoque_movimentacoes), retorna o 1º usuário ativo como fallback caso o ID seja inválido
     * @return int|null ID válido na tabela usuarios ou null
     */
    public static function validarUsuarioId(?int $usuarioId, bool $obrigatorio = false): ?int
    {
        if ($usuarioId !== null && $usuarioId > 0) {
            if (isset(self::$validUserIdsCache[$usuarioId])) {
                if (self::$validUserIdsCache[$usuarioId]) {
                    return $usuarioId;
                }
            } else {
                try {
                    $existe = self::fetchColumn("SELECT id FROM usuarios WHERE id = :id LIMIT 1", ['id' => $usuarioId]);
                    $isValid = !empty($existe);
                    self::$validUserIdsCache[$usuarioId] = $isValid;
                    if ($isValid) {
                        return (int)$existe;
                    }
                } catch (\Throwable $e) {
                    // Prossegue para fallback
                }
            }
        }

        if (!$obrigatorio) {
            return null;
        }

        if (self::$fallbackActiveUserId !== null) {
            return self::$fallbackActiveUserId;
        }

        try {
            $fallback = self::fetchColumn("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            if (!empty($fallback)) {
                self::$fallbackActiveUserId = (int)$fallback;
                return self::$fallbackActiveUserId;
            }
        } catch (\Throwable $e) {
            // Ignora falha silenciosa
        }

        return $usuarioId;
    }

    /**
     * Força a renovação da conexão com o banco de dados caso tenha ocorrido timeout
     */
    public static function reconnect(): \PDO
    {
        self::$pdo = null;
        if (class_exists('Database')) {
            \Database::disconnect();
        }
        return self::getConnection();
    }

    /**
     * Inicia uma transação com o banco de dados
     */
    public static function beginTransaction(): bool
    {
        $conn = self::getConnection();
        if (!$conn->inTransaction()) {
            return $conn->beginTransaction();
        }
        return false;
    }

    /**
     * Confirma a transação atual
     */
    public static function commit(): bool
    {
        $conn = self::getConnection();
        if ($conn->inTransaction()) {
            return $conn->commit();
        }
        return false;
    }

    /**
     * Reverte a transação atual
     */
    public static function rollBack(): bool
    {
        $conn = self::getConnection();
        if ($conn->inTransaction()) {
            return $conn->rollBack();
        }
        return false;
    }

    /**
     * Verifica se há uma transação ativa
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    /**
     * Executa uma consulta preparada com parâmetros e retorna o PDOStatement
     * Conta com reconexão automática resiliente contra timeouts (MySQL server has gone away)
     *
     * @param string $sql Consulta SQL com marcadores nomeados ou posicionais
     * @param array $params Parâmetros para binding
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $ehQuedaConexao = (
                str_contains($msg, '2006') ||
                str_contains($msg, '2013') ||
                stripos($msg, 'server has gone away') !== false ||
                stripos($msg, 'Lost connection') !== false
            );

            // Se for queda transitória de conexão e não estivermos no meio de transação, tenta reconectar uma vez
            if ($ehQuedaConexao && !self::inTransaction()) {
                self::reconnect();
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }

            throw $e;
        }
    }

    /**
     * Executa uma consulta e retorna um único registro (ou null)
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        if (!$result) {
            return null;
        }
        return Sanitizer::repararUtf8($result);
    }

    /**
     * Executa uma consulta e retorna o valor de uma única coluna
     */
    public static function fetchColumn(string $sql, array $params = [], int $columnNumber = 0): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn($columnNumber);
    }

    /**
     * Executa uma consulta e retorna todos os registros
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetchAll();
        if (empty($result)) {
            return [];
        }
        return Sanitizer::repararUtf8($result);
    }

    /**
     * Executa uma instrução INSERT, UPDATE ou DELETE e retorna o número de linhas afetadas
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Retorna o ID gerado pelo último INSERT
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Busca um registro pelo ID
     */
    public function find(mixed $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Retorna todos os registros ativos da tabela
     */
    public function all(string $orderBy = 'id ASC', int $limit = 1000): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$limit}";

        return self::fetchAll($sql);
    }

    /**
     * Insere um novo registro na tabela
     */
    public function create(array $data): int
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }

        $cleanData = [];
        foreach ($data as $field => $val) {
            $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field);
            if ($safeField !== '') {
                $cleanData[$safeField] = $val;
            }
        }

        $fields = array_keys($cleanData);
        $columns = implode(', ', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        self::execute($sql, $cleanData);

        return (int)self::lastInsertId();
    }

    /**
     * Atualiza um registro existente
     */
    public function update(mixed $id, array $data): int
    {
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $cleanData = [];
        $setParts = [];
        foreach ($data as $field => $val) {
            $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$field);
            if ($safeField !== '') {
                $cleanData[$safeField] = $val;
                $setParts[] = "{$safeField} = :{$safeField}";
            }
        }

        if (empty($setParts)) {
            return 0;
        }

        $setString = implode(', ', $setParts);
        $cleanData['id_param'] = $id;
        $sql = "UPDATE {$this->table} SET {$setString} WHERE {$this->primaryKey} = :id_param";

        return self::execute($sql, $cleanData);
    }

    /**
     * Exclui um registro (ou aplica soft delete com preenchimento de deleted_at)
     */
    public function delete(mixed $id): int
    {
        if ($this->softDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE {$this->primaryKey} = :id";
            return self::execute($sql, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'id'         => $id
            ]);
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return self::execute($sql, ['id' => $id]);
    }
}
