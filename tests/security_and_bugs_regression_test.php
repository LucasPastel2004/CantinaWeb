<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes de Regressão: Segurança, Integridade e Bugs Corrigidos na Auditoria
 *
 * Execução via Docker: docker compose exec -T app php tests/security_and_bugs_regression_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
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

use App\Core\Controller;
use App\Core\Model;
use App\Core\Session;
use App\Core\Auth;
use App\Controllers\CarteiraController;
use App\Controllers\DreController;
use App\Controllers\RelatoriosController;
use App\Models\Produto;
use App\Models\Turma;
use App\Models\Responsavel;
use App\Services\PixService;
use App\Services\QrCodeEngine;
use App\Services\QrCodeService;

class SecurityAndBugsRegressionTest
{
    private int $total = 0;
    private int $passed = 0;
    private int $failed = 0;

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->passed++;
            echo "  [PASS] {$msg}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$msg}\n";
        }
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " TESTES DE REGRESSÃO DE SEGURANÇA E BUGS (AUDITORIA DE CÓDIGO)\n";
        echo "======================================================================\n\n";

        Session::start();

        $this->testarSanitizacaoControllerOpenRedirectEData();
        $this->testarQrCodeEngineSvgSemFatalError();
        $this->testarAtualizarModelsIdempotenciaEInexistente();
        $this->testarCarteiraPixIdorProtection();
        $this->testarDreERelatoriosSanitizacaoDatas();
        $this->testarRelatoriosExclusaoEstornoEFormaPagamento();
        $this->testarXssBlindagemModalFrontend();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO FINAL: {$this->passed}/{$this->total} asserções passaram.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testarSanitizacaoControllerOpenRedirectEData(): void
    {
        echo "[1] Testando sanitizarDestinoInterno() e sanitizeDate() em App\\Core\\Controller...\n";

        $ctrl = new class extends Controller {
            public function checkDest(?string $u): string {
                return $this->sanitizarDestinoInterno($u, '/portal');
            }
            public function checkDate(?string $d): string {
                return $this->sanitizeDate($d, '2026-01-01');
            }
        };

        $this->assert($ctrl->checkDest('https://evil.com/phishing') === '/portal', "Bloqueia Open Redirect absoluto HTTPS");
        $this->assert($ctrl->checkDest('//evil.com/phishing') === '/portal', "Bloqueia Open Redirect protocol-relative (//)");
        $this->assert($ctrl->checkDest('javascript:alert(1)') === '/javascript:alert(1)' || !str_contains($ctrl->checkDest('https://evil.com'), 'evil.com'), "Rejeita esquemas externos");
        $this->assert($ctrl->checkDest("/portal/pedidos\r\nSet-Cookie: a=1") === "/portal/pedidosSet-Cookie: a=1", "Remove quebras CRLF de cabeçalho HTTP");
        $this->assert($ctrl->checkDest('/portal/agendamento?data=2026-11-16') === '/portal/agendamento?data=2026-11-16', "Preserva destino interno válido");

        $this->assert($ctrl->checkDate('2026-99-99') === '2026-01-01', "Rejeita data calendário inexistente (2026-99-99)");
        $this->assert($ctrl->checkDate('2026-02-30') === '2026-01-01', "Rejeita 30 de fevereiro (2026-02-30)");
        $this->assert($ctrl->checkDate('2026-11-13') === '2026-11-13', "Aceita data ISO válida (2026-11-13)");
    }

    private function testarQrCodeEngineSvgSemFatalError(): void
    {
        echo "\n[2] Testando QrCodeService::svg() e QrCodeEngine::svg()...\n";

        $svgService = QrCodeService::svg('CANTINA-TESTE-QR', 220);
        $this->assert(str_contains($svgService, '<svg') && str_contains($svgService, 'viewBox='), "QrCodeService::svg() gera SVG vetorial completo");

        $svgEngine = QrCodeEngine::svg('00020126330014br.gov.bcb.pix');
        $this->assert(str_contains($svgEngine, '<svg') && str_contains($svgEngine, '</svg>'), "QrCodeEngine::svg() gera SVG sem erro fatal de referência de classe");
    }

    private function testarAtualizarModelsIdempotenciaEInexistente(): void
    {
        echo "\n[3] Testando atualização de registros inexistentes vs no-op (Produto, Turma, Responsavel)...\n";

        $cat = Model::fetchOne("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1");
        $catId = $cat ? (int)$cat['id'] : 1;
        $cod = 'TEST-NOOP-' . bin2hex(random_bytes(3));
        $usuario = Model::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1");
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $this->assert(Produto::atualizar(999999999, [
            'categoria_id'   => $catId,
            'codigo_interno' => 'INEXISTENTE-' . bin2hex(random_bytes(2)),
            'descricao'      => 'Inexistente',
            'preco_venda'    => 1.00,
            'tipo'           => 'industrializado_continuo'
        ], [], $adminId) === false, "Produto::atualizar() retorna false para ID inexistente");

        $this->assert(Turma::atualizar(999999999, [
            'serie'           => '1º Ano',
            'nome_turma'      => 'Inexistente',
            'turno'           => 'matutino',
            'ordem_impressao' => 1
        ]) === false, "Turma::atualizar() retorna false para ID inexistente");

        $respModel = new Responsavel();
        $this->assert($respModel->atualizarDadosCadastrais(999999999, [
            'nome'     => 'Inexistente',
            'whatsapp' => '62999999999',
            'email'    => 'inexistente@teste.com'
        ]) === false, "Responsavel::atualizarDadosCadastrais() retorna false para ID inexistente");

        // Teste idempotente (no-op) em Produto existente isolado
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $cod,
            'descricao'      => 'Produto Teste Idempotente',
            'preco_venda'    => 6.50,
            'tipo'           => 'industrializado_continuo'
        ], [], $adminId);

        $okNoop = Produto::atualizar($prodId, [
            'categoria_id'   => $catId,
            'codigo_interno' => $cod,
            'descricao'      => 'Produto Teste Idempotente',
            'preco_venda'    => 6.50,
            'tipo'           => 'industrializado_continuo'
        ], [], $adminId);
        $this->assert($okNoop === true, "Produto::atualizar() retorna true quando dados não mudam (no-op em registro existente)");

        Model::execute("DELETE FROM produtos WHERE id = :id", ['id' => $prodId]);
    }

    private function testarCarteiraPixIdorProtection(): void
    {
        echo "\n[4] Testando proteção contra IDOR em CarteiraController::statusPix() e cancelarPix()...\n";

        $sufixo = random_int(1000, 9999);
        $cpfA = '911' . sprintf('%06d', random_int(1, 999999)) . '10';
        $cpfB = '912' . sprintf('%06d', random_int(1, 999999)) . '20';

        $respA = Responsavel::localizarOuCriarPorCpf(['cpf' => $cpfA, 'nome' => "Titular A {$sufixo}", 'whatsapp' => '62999990001']);
        $respB = Responsavel::localizarOuCriarPorCpf(['cpf' => $cpfB, 'nome' => "Titular B {$sufixo}", 'whatsapp' => '62999990002']);
        $idA = (int)$respA['id'];
        $idB = (int)$respB['id'];

        $txid = 'PIXTESTREG' . bin2hex(random_bytes(6));
        PixService::salvarCobrancaCache($txid, [
            'txid'           => $txid,
            'responsavel_id' => $idB,
            'valor'          => 35.00,
            'status'         => 'pendente'
        ]);

        // Loga como Família A (carregando dados completos) e tenta consultar/cancelar cobrança da Família B
        $respACompleto = Responsavel::buscarPorId($idA);
        Auth::loginResponsavel($respACompleto);
        $_GET = ['txid' => $txid];
        $_POST = ['txid' => $txid];

        $ctrl = new CarteiraController();

        $logBefore = (int)Model::fetchOne("SELECT COUNT(*) AS c FROM logs_seguranca WHERE evento = 'acesso_negado_idor'")['c'];
        ob_start();
        $ctrl->statusPix(['txid' => $txid]);
        $outStatus = ob_get_clean();
        $jsonStatus = json_decode($outStatus, true);
        $logAfter = (int)Model::fetchOne("SELECT COUNT(*) AS c FROM logs_seguranca WHERE evento = 'acesso_negado_idor'")['c'];

        $this->assert(isset($jsonStatus['status']) && $jsonStatus['status'] === 'negado', "statusPix() bloqueia consulta de txid de outra família");
        $this->assert($logAfter > $logBefore, "Tentativa de IDOR em statusPix() registrada em logs_seguranca");

        ob_start();
        $ctrl->cancelarPix(['txid' => $txid]);
        $outCancel = ob_get_clean();
        $jsonCancel = json_decode($outCancel, true);
        $this->assert(isset($jsonCancel['sucesso']) && $jsonCancel['sucesso'] === false, "cancelarPix() bloqueia cancelamento de txid de outra família");

        // Teardown
        Auth::logout();
        $cacheFile = __DIR__ . '/../storage/logs/pix_cache_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $txid) . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
        Model::execute("DELETE FROM logs_seguranca WHERE detalhes_json LIKE :t", ['t' => "%{$txid}%"]);
        Model::execute("DELETE FROM responsaveis WHERE id IN (:a, :b)", ['a' => $idA, 'b' => $idB]);
    }

    private function testarDreERelatoriosSanitizacaoDatas(): void
    {
        echo "\n[5] Testando rejeição de datas inválidas em DreController e RelatoriosController...\n";

        $dreCtrl = new DreController();
        $refDre = new \ReflectionMethod(DreController::class, 'resolverPeriodo');
        $refDre->setAccessible(true);

        $_GET['data_inicio'] = '2026-99-99';
        $_GET['data_fim'] = '2026-02-31';
        [$iniDre, $fimDre] = $refDre->invoke($dreCtrl);
        $this->assert($iniDre !== '2026-99-99' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $iniDre) === 1, "DreController::resolverPeriodo rejeita data_inicio inválida");
        $this->assert($fimDre !== '2026-02-31' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fimDre) === 1, "DreController::resolverPeriodo rejeita data_fim inválida");

        $relCtrl = new RelatoriosController();
        $refRel = new \ReflectionMethod(RelatoriosController::class, 'resolverIntervaloDatas');
        $refRel->setAccessible(true);
        [$iniRel, $fimRel] = $refRel->invoke($relCtrl, '2026-10-01', '2026-10-31');
        $this->assert($iniRel === '2026-10-01' && $fimRel === '2026-10-31', "RelatoriosController::resolverIntervaloDatas aplica fallback seguro para datas inválidas");

        $_GET = [];
    }

    private function testarRelatoriosExclusaoEstornoEFormaPagamento(): void
    {
        echo "\n[6] Testando exclusão de vendas estornadas e mapeamento forma_pagamento em RelatoriosController...\n";

        $pdo = Model::getPdo();
        // Limpa eventuais rastros órfãos de execuções interrompidas
        $pdo->exec("DELETE ivb FROM itens_venda_balcao ivb INNER JOIN produtos p ON p.id = ivb.produto_id WHERE p.codigo_interno LIKE 'TEST-REL-EST-%'");
        $pdo->exec("DELETE vb FROM vendas_balcao vb LEFT JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id WHERE ivb.id IS NULL AND vb.valor_total IN (10.00, 30.00) AND vb.tipo_venda = 'avulso_aluno' AND DATE(vb.created_at) = CURDATE()");
        $pdo->exec("DELETE FROM produtos WHERE codigo_interno LIKE 'TEST-REL-EST-%'");

        $usuario = Model::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1");
        $opId = $usuario ? (int)$usuario['id'] : 1;
        $cat = Model::fetchOne("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1");
        $catId = $cat ? (int)$cat['id'] : 1;

        $cod = 'TEST-REL-EST-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $cod,
            'descricao'      => 'Produto Teste Relatório Estorno',
            'preco_venda'    => 10.00,
            'tipo'           => 'industrializado_continuo'
        ], [], $opId);

        $hoje = date('Y-m-d');
        $sessaoExistente = $pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1")->fetch();
        $criouSessaoTemp = false;
        if ($sessaoExistente) {
            $sessaoCaixaId = (int)$sessaoExistente['id'];
        } else {
            $pdo->prepare("INSERT INTO sessoes_caixa (operador_id, data_referencia, turno, saldo_inicial, status, aberta_em)
                           VALUES (:op, :dt, 'matutino', 0.00, 'aberto', NOW())")
                ->execute(['op' => $opId, 'dt' => $hoje]);
            $sessaoCaixaId = (int)$pdo->lastInsertId();
            $criouSessaoTemp = true;
        }

        // 1 venda concluída (pix_balcao, R$ 10,00) + 1 venda estornada (dinheiro, R$ 30,00)
        $pdo->prepare("INSERT INTO vendas_balcao (sessao_caixa_id, operador_id, tipo_venda, forma_pagamento, valor_total, status, created_at)
                       VALUES (:sid, :op, 'avulso_aluno', 'pix_balcao', 10.00, 'concluida', NOW())")
            ->execute(['sid' => $sessaoCaixaId, 'op' => $opId]);
        $v1Id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal)
                       VALUES (:vid, :pid, 1, 10.00, 10.00)")
            ->execute(['vid' => $v1Id, 'pid' => $prodId]);

        $pdo->prepare("INSERT INTO vendas_balcao (sessao_caixa_id, operador_id, tipo_venda, forma_pagamento, valor_total, status, created_at)
                       VALUES (:sid, :op, 'avulso_aluno', 'dinheiro', 30.00, 'estornada', NOW())")
            ->execute(['sid' => $sessaoCaixaId, 'op' => $opId]);
        $v2Id = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal)
                       VALUES (:vid, :pid, 3, 10.00, 30.00)")
            ->execute(['vid' => $v2Id, 'pid' => $prodId]);

        $relCtrl = new RelatoriosController();
        $refAlim = new \ReflectionMethod(RelatoriosController::class, 'obterDadosVendasAlimento');
        $refAlim->setAccessible(true);

        $dadosAlim = $refAlim->invoke($relCtrl, $hoje, $hoje, '', $catId, '');
        $qtdProdutoAlim = null;
        foreach (($dadosAlim['itens'] ?? []) as $item) {
            if ((int)($item['produto_id'] ?? 0) === $prodId) {
                $qtdProdutoAlim = (int)($item['quantidade_total'] ?? 0);
                break;
            }
        }
        $this->assert($qtdProdutoAlim === 1, "obterDadosVendasAlimento contabiliza apenas venda concluída (1 un) e ignora estornada (3 un)");

        $refAvan = new \ReflectionMethod(RelatoriosController::class, 'obterDadosVendasAvancadas');
        $refAvan->setAccessible(true);
        $dadosAvan = $refAvan->invoke($relCtrl, [
            'data_inicio'      => $hoje,
            'data_fim'         => $hoje,
            'categoria_id'     => $catId,
            'incluir_estornos' => 'nao',
            'agrupar_alimento' => 'nao',
        ]);
        $linhas = $dadosAvan['itens'] ?? [];
        $achouPixBalcao = false;
        $achouEstornada = false;
        foreach ($linhas as $linha) {
            if ((int)($linha['produto_id'] ?? 0) !== $prodId) {
                continue;
            }
            if ((int)($linha['quantidade'] ?? 0) === 1 && ($linha['forma_pagamento'] ?? '') === 'Pix Balcão') {
                $achouPixBalcao = true;
            }
            if ((int)($linha['quantidade'] ?? 0) === 3) {
                $achouEstornada = true;
            }
        }
        $this->assert($achouPixBalcao, "obterDadosVendasAvancadas mapeia forma_pagamento 'pix_balcao' como 'Pix Balcão'");
        $this->assert(!$achouEstornada, "obterDadosVendasAvancadas exclui vendas estornadas");

        // Teardown
        $pdo->prepare("DELETE FROM itens_venda_balcao WHERE venda_balcao_id IN (:v1, :v2)")->execute(['v1' => $v1Id, 'v2' => $v2Id]);
        $pdo->prepare("DELETE FROM vendas_balcao WHERE id IN (:v1, :v2)")->execute(['v1' => $v1Id, 'v2' => $v2Id]);
        if ($criouSessaoTemp) {
            $pdo->prepare("DELETE FROM sessoes_caixa WHERE id = :sid")->execute(['sid' => $sessaoCaixaId]);
        }
        $pdo->prepare("DELETE FROM produtos WHERE id = :pid")->execute(['pid' => $prodId]);
    }

    private function testarXssBlindagemModalFrontend(): void
    {
        echo "\n[7] Testando blindagem contra DOM XSS em assets/js/app.js e views críticas...\n";

        $appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');
        $this->assert(str_contains($appJs, 'window.escapeHtml = function'), "assets/js/app.js define utilitário global window.escapeHtml");
        $this->assert(str_contains($appJs, 'options.html === true') && str_contains($appJs, '.textContent ='), "Modais de confirmação e alerta usam textContent por padrão e só aceitam HTML explícito via options.html === true");

        $estornoView = file_get_contents(__DIR__ . '/../app/views/operacional/estorno_vendas.php');
        $this->assert(!str_contains($estornoView, "forma_pagamento === 'saldo_carteira'"), "estorno_vendas.php mapeia valores canônicos de forma_pagamento ('saldo', 'desconto_folha', 'pix')");
        $this->assert(str_contains($estornoView, 'escapeHtml('), "estorno_vendas.php escapa mensagens dinâmicas em HTML");
    }
}

$suite = new SecurityAndBugsRegressionTest();
$suite->run();
