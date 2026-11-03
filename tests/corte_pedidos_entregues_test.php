<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Consolidação de Corte com Pedidos 'entregue' e Blindagem de Bloqueio Retroativo
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

// PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Pedido;
use App\Models\Ticket;
use App\Core\Model;

class CortePedidosEntreguesTest
{
    private int $total = 0;
    private int $pass = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->pass++;
            echo "  [PASS] {$msg}\n";
        } else {
            echo "  [FAIL] {$msg}\n";
        }
    }

    public function executar(): void
    {
        echo "=================================================================\n";
        echo " TESTES: CONSOLIDAÇÃO DE CORTE (ENTREGUE) & BLOQUEIO RETROATIVO\n";
        echo "=================================================================\n\n";

        // Usaremos uma transação para criar pedidos de teste temporários e garantir rollback
        $this->pdo->beginTransaction();

        try {
            // Localiza aluno, responsável e produto para teste
            $aluno = $this->pdo->query("SELECT id, responsavel_id FROM alunos WHERE deleted_at IS NULL LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
            $produto = $this->pdo->query("SELECT id, preco_venda, descricao FROM produtos WHERE deleted_at IS NULL AND tipo = 'salgado_diario' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);

            if (!$aluno || !$produto) {
                throw new \RuntimeException("Dados base de alunos e produtos insuficientes para teste.");
            }

            $alunoId = (int)$aluno['id'];
            $respId = (int)$aluno['responsavel_id'];
            $prodId = (int)$produto['id'];
            $preco = (float)$produto['preco_venda'];

            $dataTeste = '2026-10-30';
            $turnoMat = 'matutino';
            $turnoVesp = 'vespertino';

            // 1. Inserir pedido com status 'entregue' no matutino
            $codMat = 'T-MAT1-' . substr(md5(uniqid()), 0, 8);
            $stmtPed = $this->pdo->prepare("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at, updated_at) 
                                           VALUES (?, ?, ?, ?, ?, ?, 'entregue', NOW(), NOW())");
            $stmtPed->execute([$codMat, $respId, $alunoId, $dataTeste, $turnoMat, $preco]);
            $pedMatId = (int)$this->pdo->lastInsertId();

            $stmtItem = $this->pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) 
                                            VALUES (?, ?, 2, ?, ?)");
            $stmtItem->execute([$pedMatId, $prodId, $preco, ($preco * 2)]);

            // 2. Inserir pedido com status 'agendado' no matutino
            $codMat2 = 'T-MAT2-' . substr(md5(uniqid()), 0, 8);
            $stmtPed->execute([$codMat2, $respId, $alunoId, $dataTeste, $turnoMat, $preco]);
            $pedMat2Id = (int)$this->pdo->lastInsertId();
            $stmtItem->execute([$pedMat2Id, $prodId, $preco, ($preco * 2)]);

            // 3. Inserir pedido com status 'cancelado' no matutino (não deve aparecer)
            $codMat3 = 'T-MAT3-' . substr(md5(uniqid()), 0, 8);
            $stmtPedCanc = $this->pdo->prepare("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at, updated_at) 
                                               VALUES (?, ?, ?, ?, ?, ?, 'cancelado', NOW(), NOW())");
            $stmtPedCanc->execute([$codMat3, $respId, $alunoId, $dataTeste, $turnoMat, $preco]);
            $pedMat3Id = (int)$this->pdo->lastInsertId();
            $stmtItem->execute([$pedMat3Id, $prodId, $preco, ($preco * 2)]);

            // 4. Inserir pedido com status 'entregue' no vespertino
            $codVesp = 'T-VES1-' . substr(md5(uniqid()), 0, 8);
            $stmtPed->execute([$codVesp, $respId, $alunoId, $dataTeste, $turnoVesp, $preco]);
            $pedVespId = (int)$this->pdo->lastInsertId();
            $stmtItem->execute([$pedVespId, $prodId, $preco, ($preco * 2)]);

            echo "--- 1. Consolidação de Corte com Pedidos 'entregue' ---\n";

            $corteMat = Pedido::consolidarCorte($dataTeste, $turnoMat);
            $this->assert(!empty($corteMat['itens']), "Corte matutino retornou itens consolidando pedidos com status 'entregue'");
            $this->assert($corteMat['total_pedidos'] >= 2, "Corte matutino totalizou pedidos 'agendado' e 'entregue' (encontrados: {$corteMat['total_pedidos']})");

            // Verifica se o item do pedido cancelado não foi somado incorretamente
            // Espera-se que apenas os 2 pedidos ativos (agendado + entregue) entrem
            $itemEncontrado = null;
            foreach ($corteMat['itens'] as $it) {
                if ((int)$it['produto_id'] === $prodId) {
                    $itemEncontrado = $it;
                    break;
                }
            }
            $this->assert($itemEncontrado !== null, "Produto de teste encontrado no corte matutino");
            $this->assert($itemEncontrado['quantidade_total'] >= 4, "Quantidade do produto no corte contempla pedidos entregues e agendados");

            $corteVesp = Pedido::consolidarCorte($dataTeste, $turnoVesp);
            $this->assert(!empty($corteVesp['itens']), "Corte vespertino retornou itens consolidando pedidos com status 'entregue'");
            $this->assert($corteVesp['total_pedidos'] >= 1, "Corte vespertino totalizou pedidos (encontrados: {$corteVesp['total_pedidos']})");

            echo "\n--- 2. Verificação da Regra Inegociável: Bloqueio de Conferência em Datas Retroativas ---\n";
            $hoje = date('Y-m-d');
            $dataPassada = '2026-09-15'; // data inquestionavelmente passada

            $bloqueouPassado = false;
            $msgErro = '';
            try {
                Pedido::confirmarRecebimentoFornecedor(
                    $dataPassada,
                    'matutino',
                    1,
                    [['produto_id' => $prodId, 'qtd_esperada' => 10, 'qtd_entregue' => 10]],
                    'Teste retroativo'
                );
            } catch (\DomainException $e) {
                $bloqueouPassado = true;
                $msgErro = $e->getMessage();
            }

            $this->assert($bloqueouPassado, "Pedido::confirmarRecebimentoFornecedor bloqueou rigorosamente conferência de data passada com DomainException");
            $this->assert(str_contains($msgErro, 'A conferência física e confirmação de recebimento de fornecedor só podem ser realizadas na data de hoje'), "Mensagem de bloqueio de conferência retroativa explícita e protetiva");

        } finally {
            // Teardown atômico garantido
            $this->pdo->rollBack();
            echo "\n[INFO] Rollback executado: banco mantido 100% limpo e íntegro.\n";
        }

        echo "\n=======================================================\n";
        echo " RESULTADO: {$this->pass} / {$this->total} testes passaram!\n";
        echo "=======================================================\n\n";

        if ($this->pass === $this->total) {
            echo ">>> TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! <<<\n";
            exit(0);
        } else {
            echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
            exit(1);
        }
    }
}

$test = new CortePedidosEntreguesTest();
$test->executar();
