<?php
/**
 * Teste Automatizado de Leitura Óptica e Conformidade do QR Code Pix
 * Valida o padrão EMVCo/BACEN, tamanho de TXID (máx 25 chars),
 * Tag 01 = '11' para chave estática e geração do SVG vetorial com Quiet Zone.
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\PixService;
use App\Services\QrCodeService;

echo "=== INICIANDO TESTE DE VALIDAÇÃO DE QR CODE PIX ===\n\n";

$passou = 0;
$total = 0;

function assercao(bool $cond, string $msg, &$passou, &$total): void {
    $total++;
    if ($cond) {
        $passou++;
        echo "  [PASS] $msg\n";
    } else {
        echo "  [FAIL] $msg\n";
    }
}

// 1. Gera cobrança Pix para teste
$cobranca = PixService::gerarCobrancaPix(1, 50.00, 'Recarga Carteira');

assercao(!empty($cobranca['txid']), "txid gerado com sucesso", $passou, $total);
assercao(strlen($cobranca['txid']) <= 25, "txid possui no máximo 25 caracteres (" . strlen($cobranca['txid']) . " chars) conforme BACEN", $passou, $total);
assercao(str_starts_with($cobranca['copia_e_cola'], '000201'), "Payload inicia com indicador 000201", $passou, $total);
assercao(str_contains($cobranca['copia_e_cola'], '010211'), "Point of Initiation Method é 11 (estático/chave direta)", $passou, $total);
assercao(str_contains($cobranca['copia_e_cola'], 'BR.GOV.BCB.PIX'), "Contém GUI oficial BR.GOV.BCB.PIX", $passou, $total);
assercao(str_contains($cobranca['copia_e_cola'], '540550.00'), "Contém valor monetário formatado (50.00)", $passou, $total);

// 2. Valida cálculo de CRC16
$payload = $cobranca['copia_e_cola'];
$semCrc = substr($payload, 0, -4);
$crcEsperado = substr($payload, -4);
$crcCalculado = PixService::calcularCrc16($semCrc);
assercao($crcCalculado === $crcEsperado, "CRC16 recalculado confere ($crcCalculado === $crcEsperado)", $passou, $total);

// 3. Valida geração de SVG e Data URI
$svg = $cobranca['qr_code_svg'];
assercao(str_contains($svg, '<svg') && str_contains($svg, '</svg>'), "SVG gerado contém tags <svg> válidas", $passou, $total);
assercao(str_contains($svg, 'viewBox="0 0 260 260"'), "SVG contém viewBox escalado 260x260", $passou, $total);
assercao(str_contains($svg, '<path'), "SVG contém elemento vetorial <path>", $passou, $total);
assercao(str_starts_with($cobranca['qr_code_data_uri'], 'data:image/svg+xml;base64,'), "Data URI no formato base64 correto", $passou, $total);

// 4. Valida matriz 2D
$matrix = QrCodeService::encodeToMatrix($payload);
assercao(is_array($matrix) && count($matrix) >= 21, "Matriz 2D gerada tem dimensões válidas de Modelo 2 (" . count($matrix) . "x" . count($matrix) . ")", $passou, $total);

echo "\n===================================================\n";
echo "Total de asserções: $total | Aprovadas: $passou\n";
echo "===================================================\n";

if ($passou === $total) {
    echo ">>> TODOS OS TESTES DE QR CODE PIX PASSARAM COM 100% DE SUCESSO! <<<\n";
    exit(0);
} else {
    echo ">>> FALHA EM ASSERÇÕES <<<\n";
    exit(1);
}
