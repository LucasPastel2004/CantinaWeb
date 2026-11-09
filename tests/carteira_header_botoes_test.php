<?php
/**
 * Teste Automatizado: Botões do Cabeçalho da Carteira Digital Familiar
 */
defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

echo "\n====================================================================\n";
echo " SUÍTE DE TESTES: CABEÇALHO DA CARTEIRA DIGITAL FAMILIAR\n";
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

$viewPath = __DIR__ . '/../app/views/portal/carteira.php';
$content = file_get_contents($viewPath);

// Extrai o bloco do cabeçalho
preg_match('/<!-- Cabeçalho Executivo -->.*?<\/div>\s*<\/div>/s', $content, $matches);
$headerBlock = $matches[0] ?? '';

assertTeste(!empty($headerBlock), 'Bloco do cabeçalho executivo localizado na view');
assertTeste(strpos($headerBlock, 'Extrato do Aluno (PDF)') !== false, 'Botão "Extrato do Aluno (PDF)" está presente');
assertTeste(preg_match('/<a[^>]+href="[^"]*\/portal\/extrato"[^>]+class="[^"]*btn-primary[^"]*"/', $headerBlock) === 1, 'Botão "Extrato do Aluno (PDF)" utiliza o padrão azul corporativo (btn-primary)');
assertTeste(strpos($headerBlock, 'Recarregar Saldo (Pix)') === false, 'Botão redundante "Recarregar Saldo (Pix)" foi removido do cabeçalho');
assertTeste(strpos($headerBlock, 'href="#secao-recarga"') === false, 'Link âncora #secao-recarga foi removido do cabeçalho');
assertTeste(strpos($headerBlock, 'Agendar Lanches') !== false, 'Botão "Agendar Lanches" permanece no cabeçalho');
assertTeste(preg_match('/<a[^>]+href="[^"]*\/portal\/agendamento"[^>]+class="[^"]*btn-primary[^"]*"/', $headerBlock) === 1, 'Botão "Agendar Lanches" utiliza o padrão azul corporativo (btn-primary)');

// Assegura que a seção de recarga Pix dinâmica real continua intacta no corpo da página
assertTeste(strpos($content, 'id="secao-recarga"') !== false, 'Seção principal de recarga Pix (#secao-recarga) continua presente na página');

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO DOS TESTES DE BOTÕES DA CARTEIRA:\n";
echo " Total de asserções: " . ($passou + $falhou) . "\n";
echo " Aprovadas:          $passou\n";
echo " Reprovadas:         $falhou\n";
echo " Taxa de sucesso:    " . round(($passou / ($passou + $falhou)) * 100, 2) . "%\n";
echo "--------------------------------------------------------------------\n\n";

if ($falhou > 0) {
    exit(1);
}
exit(0);
