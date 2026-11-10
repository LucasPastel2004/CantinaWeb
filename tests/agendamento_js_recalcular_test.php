<?php
/**
 * Teste de Validação: JavaScript da Comanda de Agendamento Semanal
 * Verifica ordenação de variáveis no script para evitar Temporal Dead Zone (TDZ)
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

$totalAsserts = 0;
$passedAsserts = 0;

function assertCond(bool $cond, string $msg): void {
    global $totalAsserts, $passedAsserts;
    $totalAsserts++;
    if ($cond) {
        $passedAsserts++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
}

echo "====================================================================\n";
echo " TESTE DE SINTAXE E ESCOPO JS NA COMANDA DE AGENDAMENTO\n";
echo "====================================================================\n\n";

$viewContent = file_get_contents(__DIR__ . '/../app/views/portal/agendamento.php');

$posDeclSaldo = strpos($viewContent, 'const saldoRestante = saldoDisponivel - total;');
$posUsoMobile = strpos($viewContent, 'saldoRestante < 0');

assertCond($posDeclSaldo !== false, "Declaração de 'const saldoRestante' encontrada");
assertCond($posUsoMobile !== false, "Uso de 'saldoRestante < 0' encontrado");
assertCond($posDeclSaldo < $posUsoMobile, "'saldoRestante' é declarada ANTES de qualquer leitura/uso (sem erro de TDZ)");

// Verifica se alert nativo foi substituído por alertaAcao
assertCond(!preg_match('/alert\([\'"]Limite máximo/i', $viewContent), "Alerta nativo alert() substituído por window.alertaAcao");

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: {$passedAsserts}/{$totalAsserts} asserções aprovadas com sucesso!\n";
echo "--------------------------------------------------------------------\n";

if ($passedAsserts === $totalAsserts) {
    exit(0);
} else {
    exit(1);
}
