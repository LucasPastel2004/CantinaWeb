<?php
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/ErrorHandler.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Controllers\PortalController;
use App\Controllers\RelatoriosController;

Session::start();
$resp = \App\Models\Responsavel::fetchOne("SELECT id, nome, cpf, email, whatsapp, saldo_atual, bloqueado_med FROM responsaveis LIMIT 1");
if (!$resp) {
    echo "Nenhum responsavel no banco para teste\n";
    exit(1);
}
Auth::loginResponsavel($resp);

$controller = new PortalController();

echo "=================================================\n";
echo "TESTE: DATAS DO EXTRATO DO ALUNO INICIANDO LIMPAS\n";
echo "=================================================\n";

// CENÁRIO 1: Acesso direto à tela /portal/extrato (sem parâmetros de data)
$_GET = [];
$_POST = [];
ob_start();
$controller->extrato();
$html1 = ob_get_clean();

$assert1 = [
    'Data inicial inicia vazia (value="")' => str_contains($html1, 'id="dataInicio" class="form-control form-control-sm font-tabular" value=""'),
    'Data final inicia vazia (value="")' => str_contains($html1, 'id="dataFim" class="form-control form-control-sm font-tabular" value=""'),
    'Não contém data do primeiro dia do mês por padrão no input' => !str_contains($html1, 'id="dataInicio" class="form-control form-control-sm font-tabular" value="' . date('Y-m-01') . '"'),
    'Contém atalho "Todos / Geral"' => str_contains($html1, "definirPeriodo('todos')"),
];

$erros = 0;
foreach ($assert1 as $desc => $pass) {
    if ($pass) {
        echo "  [PASS] Cenário 1: " . $desc . "\n";
    } else {
        echo "  [FAIL] Cenário 1: " . $desc . "\n";
        $erros++;
    }
}

// CENÁRIO 2: Submeter filtro geral ou filtrar com datas limpas
$_GET = ['filtrar' => '1', 'filtro_geral' => '1'];
ob_start();
$controller->extrato();
$html2 = ob_get_clean();

$assert2 = [
    'Exibe texto "Todo o Histórico (Geral)" no período' => str_contains($html2, 'Todo o Histórico (Geral)'),
    'Data inicial permanece limpa no input' => str_contains($html2, 'id="dataInicio" class="form-control form-control-sm font-tabular" value=""'),
    'Data final permanece limpa no input' => str_contains($html2, 'id="dataFim" class="form-control form-control-sm font-tabular" value=""'),
];

foreach ($assert2 as $desc => $pass) {
    if ($pass) {
        echo "  [PASS] Cenário 2: " . $desc . "\n";
    } else {
        echo "  [FAIL] Cenário 2: " . $desc . "\n";
        $erros++;
    }
}

// CENÁRIO 3: Filtrar com datas específicas
$dataIniTeste = '2026-11-01';
$dataFimTeste = '2026-11-09';
$_GET = ['filtrar' => '1', 'filtro_geral' => '0', 'data_inicio' => $dataIniTeste, 'data_fim' => $dataFimTeste];
ob_start();
$controller->extrato();
$html3 = ob_get_clean();

$assert3 = [
    'Data inicial preenchida no input' => str_contains($html3, 'value="' . $dataIniTeste . '"'),
    'Data final preenchida no input' => str_contains($html3, 'value="' . $dataFimTeste . '"'),
    'Exibe intervalo formatado no card' => str_contains($html3, '01/11/2026') && str_contains($html3, '09/11/2026'),
];

foreach ($assert3 as $desc => $pass) {
    if ($pass) {
        echo "  [PASS] Cenário 3: " . $desc . "\n";
    } else {
        echo "  [FAIL] Cenário 3: " . $desc . "\n";
        $erros++;
    }
}

if ($erros === 0) {
    echo "\nTODOS OS CENÁRIOS DE DATAS LIMPAS PASSARAM COM SUCESSO!\n";
    exit(0);
} else {
    echo "\nHOUVE {$erros} FALHA(S) NO TESTE!\n";
    exit(1);
}
