<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Simulação de Requisições Web no Front Controller (index.php)
 */

define('CANTINA_TEST_RUN', true);

function testUrl(string $method, string $uri, array $postData = [], array $headers = []): array {
    $script = __DIR__ . '/../index.php';
    
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_POST = $postData;
    $_GET = [];
    
    foreach ($headers as $k => $v) {
        $_SERVER[$k] = $v;
    }
    
    // Captura saída
    ob_start();
    try {
        require $script;
    } catch (\Throwable $e) {
        // Exceções capturadas pelo ErrorHandler encerram com saída renderizada
    }
    $output = ob_get_clean();
    
    return [
        'status' => http_response_code(),
        'output' => $output
    ];
}

echo "=== TESTE DE REQUISIÇÕES VIA INDEX.PHP ===" . PHP_EOL;

// 1. Teste da Raiz /
$_SERVER = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
ob_start();
require __DIR__ . '/../index.php';
$html = ob_get_clean();

$passouHome = str_contains($html, 'Colégio Monteiro Lobato') && (str_contains($html, 'Sistema Completo') || str_contains($html, 'Fase'));
echo "1. Rota GET / -> " . ($passouHome ? "SUCESSO (HTML Renderizado)" : "FALHA") . PHP_EOL;

// 2. Teste da API /api/status via script separado
$codeApi = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/api/status";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_SERVER["HTTP_ACCEPT"] = "application/json";
require "index.php";
';
$outputApi = shell_exec("php -r " . escapeshellarg($codeApi));
$jsonApi = json_decode($outputApi, true);
$passouApi = isset($jsonApi['status']) && $jsonApi['status'] === 'online' && isset($jsonApi['mvc_core']);
echo "2. Rota GET /api/status -> " . ($passouApi ? "SUCESSO (JSON Válido retornado)" : "FALHA") . PHP_EOL;

// 3. Teste das Rotas de Debug Removidas (/teste/555 e /teste-erro devem retornar 404)
$codeRotaRemovida = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/teste/555";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$outputRotaRemovida = shell_exec("php -r " . escapeshellarg($codeRotaRemovida));
$passouRemovida = str_contains($outputRotaRemovida, '404 — Página Não Encontrada');
echo "3. Rota Debug Removida /teste/555 -> " . ($passouRemovida ? "SUCESSO (Bloqueada com 404)" : "FALHA") . PHP_EOL;

// 4. Teste de Rota 404 /pagina-inexistente
$code404 = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/pagina-inexistente";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$output404 = shell_exec("php -r " . escapeshellarg($code404));
$passou404 = str_contains($output404, '404 — Página Não Encontrada');
echo "4. Rota Inexistente /pagina-inexistente -> " . ($passou404 ? "SUCESSO (Tela 404 apresentada)" : "FALHA") . PHP_EOL;

// 5. Teste da Rota Pública /login com Token CSRF e Handler 500 Seguro
$codeLogin = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/login";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$outputLogin = shell_exec("php -r " . escapeshellarg($codeLogin));
$passouLogin = str_contains($outputLogin, 'csrf_token') && str_contains($outputLogin, 'Portal da Família');

$codeHandler500 = '
define("CANTINA_SISTEMA", true);
require "app/core/ErrorHandler.php";
\App\Core\ErrorHandler::handleException(new \RuntimeException("Erro simulado controlado em teste"));
';
$outputHandler500 = shell_exec("php -r " . escapeshellarg($codeHandler500));
$passouErro = str_contains($outputHandler500, 'Ocorreu uma falha no processamento') && str_contains($outputHandler500, 'Identificador do Incidente:');
echo "5. Rota GET /login (CSRF) e ErrorHandler 500 -> " . (($passouLogin && $passouErro) ? "SUCESSO (Login CSRF + Tela 500 segura com UUID)" : "FALHA") . PHP_EOL;

if ($passouHome && $passouApi && $passouRemovida && $passou404 && $passouLogin && $passouErro) {
    echo PHP_EOL . "STATUS: TODAS AS 5 SIMULAÇÕES WEB PASSARAM COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "STATUS: FALHA NAS SIMULAÇÕES WEB!" . PHP_EOL;
    exit(1);
}
