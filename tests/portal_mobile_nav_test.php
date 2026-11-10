<?php
/**
 * Teste Automatizado da Barra de Navegação Inferior do Portal da Família
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';

use App\Core\Auth;
use App\Core\Session;

Session::start();

echo "======================================================================\n";
echo "  TESTE DA BARRA DE NAVEGAÇÃO MOBILE (6 ABAS) NO PORTAL DA FAMÍLIA   \n";
echo "======================================================================\n\n";

$pass = 0;
$fail = 0;

function assertCondition($cond, $desc, &$pass, &$fail) {
    if ($cond) {
        echo "  [PASS] $desc\n";
        $pass++;
    } else {
        echo "  [FAIL] $desc\n";
        $fail++;
    }
}

// 1. Simular renderização com layout do portal
$baseUrl = '';
$pageTitle = 'Teste Navegação';
$activeMenu = 'alergias';
$saldoAtual = 50.00;
$responsavelNome = 'Responsável Teste';
$csrf_field = '';
$content = '<div>Conteúdo de Teste</div>';

ob_start();
require __DIR__ . '/../app/views/layouts/portal.php';
$html = ob_get_clean();

// 2. Validações da barra superior
assertCondition(
    str_contains($html, 'class="portal-nav-bar d-none d-md-block"'),
    "Barra de abas superior possui classe 'd-none d-md-block' (oculta no mobile)",
    $pass, $fail
);

// 3. Validações da barra inferior fixa
assertCondition(
    str_contains($html, '<nav class="mobile-bottom-nav d-md-none"'),
    "Barra inferior fixa '.mobile-bottom-nav d-md-none' renderizada",
    $pass, $fail
);

// 4. Validação da descentralização do botão Agendar
assertCondition(
    !str_contains($html, 'mobile-nav-highlight'),
    "Botão 'Agendar' não contém mais a classe 'mobile-nav-highlight' (descentralizado com sucesso)",
    $pass, $fail
);

assertCondition(
    !str_contains($html, 'mobile-nav-cta-circle'),
    "Botão 'Agendar' não contém mais o círculo elevado 'mobile-nav-cta-circle'",
    $pass, $fail
);

// 5. Validação das 6 abas
$abasEsperadas = [
    '/portal' => 'Início',
    '/portal/carteira' => 'Carteira',
    '/portal/agendamento' => 'Agendar',
    '/portal/pedidos' => 'Pedidos',
    '/portal/extrato' => 'Extrato',
    '/portal/alergias' => 'Alergias',
];

foreach ($abasEsperadas as $href => $label) {
    assertCondition(
        str_contains($html, 'href="' . $href . '"') && str_contains($html, '<span>' . $label . '</span>'),
        "Aba '$label' ($href) está presente na barra inferior",
        $pass, $fail
    );
}

// 6. Validação da ordem exata: 3 à esquerda e 3 à direita
// Extrair os itens da barra inferior
preg_match('/<nav class="mobile-bottom-nav.*<\/nav>/s', $html, $matches);
$navHtml = $matches[0] ?? '';

$posInicio = strpos($navHtml, '<span>Início</span>');
$posCarteira = strpos($navHtml, '<span>Carteira</span>');
$posAgendar = strpos($navHtml, '<span>Agendar</span>');
$posPedidos = strpos($navHtml, '<span>Pedidos</span>');
$posExtrato = strpos($navHtml, '<span>Extrato</span>');
$posAlergias = strpos($navHtml, '<span>Alergias</span>');

$ordemCorreta = ($posInicio < $posCarteira) &&
                ($posCarteira < $posAgendar) &&
                ($posAgendar < $posPedidos) &&
                ($posPedidos < $posExtrato) &&
                ($posExtrato < $posAlergias);

assertCondition(
    $ordemCorreta,
    "Ordem exata confirmada: 3 à esquerda (Início, Carteira, Agendar) e 3 à direita (Pedidos, Extrato, Alergias)",
    $pass, $fail
);

// 7. Validação da aba ativa na barra inferior
assertCondition(
    str_contains($html, 'href="/portal/alergias" class="mobile-bottom-nav-item active"'),
    "Aba 'Alergias' está devidamente marcada como active na barra inferior",
    $pass, $fail
);

// 8. Validação da versão do asset para cache busting
assertCondition(
    str_contains($html, 'custom.css?v=7.4.3'),
    "Versão do custom.css incrementada para 7.4.3 no layout master",
    $pass, $fail
);

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: $pass passaram, $fail falharam.\n";
echo "----------------------------------------------------------------------\n";

if ($fail > 0) {
    exit(1);
}
exit(0);