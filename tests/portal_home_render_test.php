<?php
/**
 * Teste de Renderização: Início do Portal da Família
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Controllers\PortalController;

Session::start();
$resp = \App\Models\Responsavel::fetchOne("SELECT id, nome, cpf, email, whatsapp, saldo_atual, bloqueado_med FROM responsaveis LIMIT 1");
if (!$resp) {
    echo "Nenhum responsavel no banco" . PHP_EOL;
    exit(1);
}
Auth::loginResponsavel($resp);

$controller = new PortalController();

ob_start();
$controller->index();
$html = ob_get_clean();

echo "=================================================" . PHP_EOL;
echo "TESTE DE RENDERIZACAO DO NOVO PORTAL DA FAMILIA" . PHP_EOL;
echo "=================================================" . PHP_EOL;

$asserts = [
    'HTML gerado com sucesso (> 2000 bytes)' => strlen($html) > 2000,
    'Contém saudação formatada (Olá,)' => str_contains($html, 'Olá,'),
    'Contém pilares de cartões (portal-pillar-card)' => str_contains($html, 'portal-pillar-card'),
    'Contém saldo formatado (R$)' => str_contains($html, 'R$'),
    'Contém atalhos de ação (action-tile-portal)' => str_contains($html, 'action-tile-portal'),
    'Contém grade de horários da cantina' => str_contains($html, 'Rotina da Cantina') && str_contains($html, 'Bloqueio de Corte'),
    'Contém seção de últimos pedidos' => str_contains($html, 'Últimos Pedidos & Lanches Agendados'),
    'Contém versão de asset 7.2.2' => str_contains($html, 'custom.css?v=7.2.2'),
];

$allPassed = true;
foreach ($asserts as $label => $pass) {
    if ($pass) {
        echo "  [PASS] {$label}" . PHP_EOL;
    } else {
        echo "  [FAIL] {$label}" . PHP_EOL;
        $allPassed = false;
    }
}

if ($allPassed) {
    echo PHP_EOL . "TODAS AS ASSERÇÕES DE RENDERIZAÇÃO PASSARAM COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "FALHA EM UMA OU MAIS ASSERÇÕES!" . PHP_EOL;
    exit(1);
}
