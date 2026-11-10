<?php
/**
 * Teste Automatizado: Exibição Estrita do Usuário Autenticado na Topbar do Portal da Família
 * Arquivo: tests/portal_topbar_usuario_logado_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/services/PixService.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';
require_once __DIR__ . '/../app/controllers/CarteiraController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Models\TransacaoCarteira;
use App\Controllers\PortalController;
use App\Controllers\CarteiraController;

$totalAsserts = 0;
$passedAsserts = 0;

function assertCondition(bool $cond, string $msg): void {
    global $totalAsserts, $passedAsserts;
    $totalAsserts++;
    if ($cond) {
        $passedAsserts++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
}

echo "\n======================================================================\n";
echo " TESTE: USUÁRIO AUTENTICADO E TOPBAR NO PORTAL DA FAMÍLIA (PAI E MÃE)\n";
echo "======================================================================\n\n";

Session::start();

// 1. Busca os registros reais da família Aelson (Pai, titular) e Danilla (Mãe, co-responsável)
$respModel = new Responsavel();
$pai = Responsavel::fetchOne("SELECT * FROM responsaveis WHERE nome LIKE '%AELSON ROMAO%' OR cpf = '79555198104' LIMIT 1");
$mae = Responsavel::fetchOne("SELECT * FROM responsaveis WHERE nome LIKE '%DANILLA DIAS%' OR cpf = '00789673177' LIMIT 1");

if (!$pai || !$mae) {
    echo "  [ERRO] Registros de Aelson ou Danilla não localizados no banco de dados.\n";
    exit(1);
}

echo "Dados da Família de Teste:\n";
echo "  Pai (Titular): ID {$pai['id']} - {$pai['nome']} (titular_id: " . var_export($pai['titular_id'], true) . ")\n";
echo "  Mãe (Co-resp): ID {$mae['id']} - {$mae['nome']} (titular_id: " . var_export($mae['titular_id'], true) . ")\n\n";

// --------------------------------------------------------------------------
// TESTE 1: Login da Mãe (Danilla) - Topbar em TODAS as Telas do Portal
// --------------------------------------------------------------------------
echo "[1] Testando Topbar com Login da MÃE (Danilla Dias da Cunha)...\n";
Auth::loginResponsavel($mae);

$userLogado = Auth::user();
assertCondition($userLogado['id'] === (int)$mae['id'], "Auth::user() retorna o ID da Mãe (886)");
assertCondition(stripos($userLogado['nome'], 'Danilla') !== false, "Auth::user() retorna o Nome da Mãe");
assertCondition((int)$userLogado['titular_id'] === (int)$pai['id'], "Auth::user() identifica titular_id como o ID do Pai");

// Função auxiliar para capturar o HTML renderizado por um método do controller
function capturarHtml(callable $callable): string {
    ob_start();
    try {
        $callable();
    } catch (\Throwable $e) {
        // Ignora saídas abruptas ou redirects normais pós-render
    }
    return ob_get_clean();
}

// 1.1 Carteira Familiar (/portal/carteira)
$carteiraController = new CarteiraController();
$htmlCarteira = capturarHtml(fn() => $carteiraController->index());

assertCondition(str_contains($htmlCarteira, 'Danilla Dias da Cunha'), "Carteira: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(preg_match('/class="portal-header-avatar[^>]*>\s*D\s*<\/span>/', $htmlCarteira) === 1, "Carteira: Topbar exibe avatar 'D'");
assertCondition(!preg_match('/<span class="portal-header-username[^>]*>\s*Aelson Romão/i', $htmlCarteira), "Carteira: Topbar NÃO exibe Aelson como usuário logado");
assertCondition(str_contains($htmlCarteira, 'Co-responsável (Conta Familiar)'), "Carteira: Dropdown exibe 'Co-responsável (Conta Familiar)'");

// 1.2 Agendamento (/portal/agendamento)
$portalController = new PortalController();
$htmlAgendamento = capturarHtml(fn() => $portalController->agendamento());

assertCondition(str_contains($htmlAgendamento, 'Danilla Dias da Cunha'), "Agendamento: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(preg_match('/class="portal-header-avatar[^>]*>\s*D\s*<\/span>/', $htmlAgendamento) === 1, "Agendamento: Topbar exibe avatar 'D'");
assertCondition(!preg_match('/<span class="portal-header-username[^>]*>\s*Aelson Romão/i', $htmlAgendamento), "Agendamento: Topbar NÃO exibe Aelson");
assertCondition(file_get_contents(__DIR__ . '/../app/views/portal/agendamento.php') && str_contains(file_get_contents(__DIR__ . '/../app/views/portal/agendamento.php'), 'href="<?php echo $baseUrl; ?>/portal/pedidos?filtrar=1"'), "Agendamento: Links para Meus Pedidos apontam para /portal/pedidos?filtrar=1");

// 1.3 Meus Pedidos (/portal/pedidos)
$htmlPedidos = capturarHtml(fn() => $portalController->pedidos());

assertCondition(str_contains($htmlPedidos, 'Danilla Dias da Cunha'), "Meus Pedidos: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(preg_match('/class="portal-header-avatar[^>]*>\s*D\s*<\/span>/', $htmlPedidos) === 1, "Meus Pedidos: Topbar exibe avatar 'D'");
assertCondition(!preg_match('/<span class="portal-header-username[^>]*>\s*Aelson Romão/i', $htmlPedidos), "Meus Pedidos: Topbar NÃO exibe Aelson");

// 1.4 Extrato do Aluno (/portal/extrato)
$htmlExtrato = capturarHtml(fn() => $portalController->extrato());

assertCondition(str_contains($htmlExtrato, 'Danilla Dias da Cunha'), "Extrato: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(preg_match('/class="portal-header-avatar[^>]*>\s*D\s*<\/span>/', $htmlExtrato) === 1, "Extrato: Topbar exibe avatar 'D'");
assertCondition(!preg_match('/<span class="portal-header-username[^>]*>\s*Aelson Romão/i', $htmlExtrato), "Extrato: Topbar NÃO exibe Aelson");

// 1.5 Perfil / Meus Dados (/portal/perfil)
$htmlPerfil = capturarHtml(fn() => $portalController->perfil());

assertCondition(str_contains($htmlPerfil, 'Danilla Dias da Cunha'), "Perfil: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(str_contains($htmlPerfil, 'Dados do Co-responsável'), "Perfil: Card 1 exibe título contextual 'Dados do Co-responsável'");
assertCondition(str_contains($htmlPerfil, 'Titular'), "Perfil: Exibe o Pai como 'Titular' na lista de membros vinculados");

// 1.6 Home do Portal (/portal)
$htmlHome = capturarHtml(fn() => $portalController->index());

assertCondition(str_contains($htmlHome, 'Danilla Dias da Cunha'), "Home: Topbar exibe 'Danilla Dias da Cunha'");
assertCondition(stripos($htmlHome, 'Olá, Danilla') !== false, "Home: Saudação exibe 'Olá, Danilla'");

// --------------------------------------------------------------------------
// TESTE 2: Login do Pai (Aelson - Titular) - Topbar e Papel
// --------------------------------------------------------------------------
echo "\n[2] Testando Topbar com Login do PAI (Aelson Romão de Farias Filho)...\n";
Auth::loginResponsavel($pai);

$userPaiLogado = Auth::user();
assertCondition($userPaiLogado['id'] === (int)$pai['id'], "Auth::user() retorna o ID do Pai (4)");
assertCondition(stripos($userPaiLogado['nome'], 'Aelson') !== false, "Auth::user() retorna o Nome do Pai");
assertCondition($userPaiLogado['titular_id'] === null, "Auth::user() titular_id é null para o Titular");

$htmlCarteiraPai = capturarHtml(fn() => $carteiraController->index());
assertCondition(str_contains($htmlCarteiraPai, 'Aelson Romão de Farias Filho'), "Carteira Pai: Topbar exibe 'Aelson Romão de Farias Filho'");
assertCondition(preg_match('/class="portal-header-avatar[^>]*>\s*A\s*<\/span>/', $htmlCarteiraPai) === 1, "Carteira Pai: Topbar exibe avatar 'A'");
assertCondition(str_contains($htmlCarteiraPai, 'Responsável Financeiro'), "Carteira Pai: Dropdown exibe 'Responsável Financeiro'");

$htmlPerfilPai = capturarHtml(fn() => $portalController->perfil());
assertCondition(str_contains($htmlPerfilPai, 'Titular da Conta Familiar'), "Perfil Pai: Card 1 exibe 'Titular da Conta Familiar'");
assertCondition(str_contains($htmlPerfilPai, 'Co-responsável'), "Perfil Pai: Exibe a Mãe como 'Co-responsável' na lista");

echo "\n======================================================================\n";
echo " RESUMO: {$passedAsserts}/{$totalAsserts} asserções aprovadas com sucesso!\n";
echo "======================================================================\n\n";

if ($passedAsserts === $totalAsserts) {
    exit(0);
} else {
    exit(1);
}
