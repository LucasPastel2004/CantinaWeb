<?php
/**
 * Teste Automatizado: Regras de Carteira, Alerta de Saldo Baixo (R$ 5,00) e Recarga Pix sem trava
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoload do MVC
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Configuracao;
use App\Models\TransacaoCarteira;
use App\Services\PixService;

echo "====================================================================\n";
echo " SUÍTE DE TESTES: REGRAS DE CARTEIRA, ALERTA E RECARGA PIX\n";
echo "====================================================================\n\n";

$passou = 0;
$falhou = 0;

function assertTeste($condicao, $mensagem) {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [PASS] $mensagem\n";
        $passou++;
    } else {
        echo "  [FAIL] $mensagem\n";
        $falhou++;
    }
}

// 1. Testa parâmetros de configuração global
$config = Configuracao::obter();
assertTeste((float)$config['alerta_saldo_baixo'] === 5.00, 'Configuração global: alerta_saldo_baixo é R$ 5,00');
assertTeste((float)$config['recarga_minima_pix'] <= 0.01, 'Configuração global: recarga_minima_pix é <= R$ 0,01 (sem trava de R$ 10,00)');

// 2. Testa lógica de disparo de alerta de saldo baixo
$pdo = Database::getInstance();
$stmt = $pdo->query("SELECT id FROM responsaveis LIMIT 1");
$respId = (int)$stmt->fetchColumn();

if ($respId > 0) {
    // Simula cálculo com saldo de R$ 10,00 (não deve alertar, pois 10 >= 5)
    $resumo = TransacaoCarteira::obterResumoCarteira($respId);
    assertTeste($resumo['alerta_saldo_baixo_valor'] === 5.00, 'Resumo da carteira reflete limite de R$ 5,00');
    
    // Testa comparação direta da regra de negócio:
    $saldo10 = 10.00;
    $alertaCom10 = ($saldo10 < $resumo['alerta_saldo_baixo_valor']);
    assertTeste(!$alertaCom10, 'Saldo de R$ 10,00 NÃO dispara alerta de saldo baixo (10 >= 5)');

    $saldo4 = 4.00;
    $alertaCom4 = ($saldo4 < $resumo['alerta_saldo_baixo_valor']);
    assertTeste($alertaCom4, 'Saldo de R$ 4,00 DISPARA alerta de saldo baixo (4 < 5)');
}

// 3. Testa view carteira.php (Template e Interface)
$viewContent = file_get_contents(__DIR__ . '/../app/views/portal/carteira.php');

// Botões de valor sugerido: 5, 10, 20, 50
assertTeste(strpos($viewContent, 'selecionarValor(5)') !== false, 'Botão de atalho + R$ 5,00 presente');
assertTeste(strpos($viewContent, 'selecionarValor(10)') !== false, 'Botão de atalho + R$ 10,00 presente');
assertTeste(strpos($viewContent, 'selecionarValor(20)') !== false, 'Botão de atalho + R$ 20,00 presente');
assertTeste(strpos($viewContent, 'selecionarValor(50)') !== false, 'Botão de atalho + R$ 50,00 presente');
assertTeste(strpos($viewContent, 'selecionarValor(100)') === false, 'Botão antigo + R$ 100,00 removido');
assertTeste(strpos($viewContent, 'selecionarValor(200)') === false, 'Botão antigo + R$ 200,00 removido');

// Campo de valor vazio por padrão com placeholder 0,00
assertTeste(strpos($viewContent, 'placeholder="0,00"') !== false, 'Campo de valor possui placeholder="0,00"');
assertTeste(strpos($viewContent, ": '50,00'") === false, 'Valor padrão pré-preenchido de 50,00 removido');

// Ausência de mínimo de 10 reais nas letras miúdas
assertTeste(strpos($viewContent, 'Mínimo: <strong') === false, 'Aviso de Mínimo no cabeçalho removido');
assertTeste(strpos($viewContent, 'Insira um valor igual ou superior a R$') === false, 'Legenda de valor igual ou superior removida');

// 4. Testa geração de cobrança Pix com valores baixos (R$ 5,00 e R$ 1,00)
try {
    $cobranca5 = PixService::gerarCobrancaPix(
        $respId ?: 1,
        5.00,
        'Recarga Teste R$ 5',
        ['nome' => 'Responsável Teste']
    );
    assertTeste(!empty($cobranca5['qr_code_svg']) || !empty($cobranca5['qr_code_data_uri']), 'PixService gerou cobrança de R$ 5,00 com QR Code');
    assertTeste(!empty($cobranca5['copia_e_cola']), 'PixService gerou linha Copia e Cola para R$ 5,00');
    assertTeste((float)$cobranca5['valor'] === 5.00, 'Valor retornado da cobrança é R$ 5,00');
} catch (\Throwable $e) {
    assertTeste(false, 'Falha ao gerar cobrança de R$ 5,00: ' . $e->getMessage());
}

// 5. Testa que valor zero ou negativo continua sendo rejeitado por segurança
$rejeitouZero = false;
try {
    PixService::gerarCobrancaPix($respId ?: 1, 0.00, 'Recarga Zero');
} catch (\InvalidArgumentException $e) {
    $rejeitouZero = true;
}
assertTeste($rejeitouZero, 'PixService rejeita valor 0.00 com InvalidArgumentException');

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO DOS TESTES DE REGRAS PIX E CARTEIRA:\n";
echo " Total de asserções: " . ($passou + $falhou) . "\n";
echo " Aprovadas:          $passou\n";
echo " Reprovadas:         $falhou\n";
echo " Taxa de sucesso:    " . round(($passou / ($passou + $falhou)) * 100, 2) . "%\n";
echo "--------------------------------------------------------------------\n\n";

if ($falhou > 0) {
    exit(1);
}
exit(0);
