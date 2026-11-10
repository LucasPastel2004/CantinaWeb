<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Emissão, Dados e Paginação do Extrato do Aluno
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

use App\Controllers\RelatoriosController;

class ExtratoAlunoPaginationTest
{
    private int $total = 0;
    private int $pass = 0;
    private int $fail = 0;

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->pass++;
            echo "  [PASS] {$msg}\n";
        } else {
            $this->fail++;
            echo "  [FAIL] {$msg}\n";
        }
    }

    public function executar(): void
    {
        echo "=== TESTE AUTOMATIZADO: EXTRATO DO ALUNO E DADOS ANALÍTICOS ===\n\n";

        // 1. Verificação da view app/views/relatorios/extrato_aluno.php
        $viewExtrato = file_get_contents(__DIR__ . '/../app/views/relatorios/extrato_aluno.php');
        $this->assert(
            strpos($viewExtrato, 'id="formExtratoAluno"') !== false,
            'View extrato_aluno.php possui formulário id="formExtratoAluno"'
        );
        $this->assert(
            strpos($viewExtrato, 'id="aluno_busca"') !== false,
            'View extrato_aluno.php possui campo autocomplete #aluno_busca'
        );
        $this->assert(
            strpos($viewExtrato, 'id="hiddenAlunoId"') !== false,
            'View extrato_aluno.php possui campo hidden #hiddenAlunoId'
        );
        $this->assert(
            strpos($viewExtrato, '/relatorios/extrato-aluno/imprimir') !== false,
            'View extrato_aluno.php possui ação de emissão /relatorios/extrato-aluno/imprimir'
        );

        // 2. Verificação estática do Controller RelatoriosController.php
        $controllerRel = file_get_contents(__DIR__ . '/../app/controllers/RelatoriosController.php');
        $this->assert(
            strpos($controllerRel, 'function extratoAluno(') !== false,
            'RelatoriosController implementa o método extratoAluno'
        );
        $this->assert(
            strpos($controllerRel, 'function obterDadosExtratoAluno(') !== false,
            'RelatoriosController implementa obterDadosExtratoAluno'
        );
        $this->assert(
            strpos($controllerRel, '$paginaRecargas') !== false,
            'RelatoriosController::extratoAluno lê parâmetro de pagina_recargas'
        );
        $this->assert(
            strpos($controllerRel, '$paginaConsumo') !== false,
            'RelatoriosController::extratoAluno lê parâmetro de pagina_consumo'
        );

        // 3. Teste funcional através de Reflection em obterDadosExtratoAluno
        $refMethod = new \ReflectionMethod(RelatoriosController::class, 'obterDadosExtratoAluno');
        $refMethod->setAccessible(true);
        $controller = new RelatoriosController();

        // Localizar um aluno existente para validação funcional
        $pdo = \Database::getInstance();
        $stmt = $pdo->query("SELECT id FROM alunos ORDER BY id ASC LIMIT 1");
        $alunoRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($alunoRow) {
            $alunoId = (int)$alunoRow['id'];
            $dataInicio = '2020-01-01';
            $dataFim = '2099-12-31';

            // Chamada paginada (padrão 20 por página)
            $dadosPaginados = $refMethod->invoke($controller, $alunoId, $dataInicio, $dataFim, false, false, 1, 1, 20, true);
            $this->assert(
                is_array($dadosPaginados),
                'obterDadosExtratoAluno retorna array estruturado com sucesso'
            );
            $this->assert(
                isset($dadosPaginados['itens_por_pagina']) && $dadosPaginados['itens_por_pagina'] === 20,
                'itens_por_pagina configurado como 20 na chamada paginada'
            );
            $this->assert(
                count($dadosPaginados['recargas']) <= 20,
                'Lista de recargas da página 1 contém no máximo 20 itens'
            );
            $this->assert(
                count($dadosPaginados['consumo']) <= 20,
                'Lista de consumo da página 1 contém no máximo 20 itens'
            );

            // Chamada não paginada ($paginar = false, para PDF e Impressão completa)
            $dadosCompletos = $refMethod->invoke($controller, $alunoId, $dataInicio, $dataFim, false, false, 1, 1, 50, false);
            $this->assert(
                count($dadosCompletos['recargas']) === (int)$dadosCompletos['total_registros_recargas'],
                'Chamada com $paginar = false retorna 100% dos registros de recargas para o PDF'
            );
            $this->assert(
                count($dadosCompletos['consumo']) === (int)$dadosCompletos['total_registros_consumo'],
                'Chamada com $paginar = false retorna 100% dos registros de consumo para o PDF'
            );
            $this->assert(
                $dadosPaginados['total_recargas'] === $dadosCompletos['total_recargas'],
                'Total recarregado (R$) é idêntico em chamadas paginadas e completas'
            );
            $this->assert(
                $dadosPaginados['total_consumo'] === $dadosCompletos['total_consumo'],
                'Total consumido (R$) é idêntico em chamadas paginadas e completas'
            );
        } else {
            $this->assert(true, 'Nenhum aluno no banco, validação funcional pulada');
        }

        echo "\nResultado: {$this->pass}/{$this->total} asserções passaram.\n";
        if ($this->pass !== $this->total) {
            exit(1);
        }
    }
}

$teste = new ExtratoAlunoPaginationTest();
$teste->executar();
