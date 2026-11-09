<?php
/**
 * Teste Automatizado: Catálogo Único de Agendamento com Categorias e Busca (Estilo Balcão PDV)
 */
defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

echo "\n====================================================================\n";
echo " SUÍTE DE TESTES: CATÁLOGO ÚNICO DE AGENDAMENTO (CATEGORIAS E BUSCA)\n";
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

// 1. Testa se o arquivo de visão agendamento.php possui os elementos necessários
$viewPath = __DIR__ . '/../app/views/portal/agendamento.php';
$viewContent = file_get_contents($viewPath);

assertTeste(strpos($viewContent, 'id="agendamentoCatNav"') !== false, 'Barra de navegação de categorias (#agendamentoCatNav) está presente no template');
assertTeste(strpos($viewContent, 'data-cat="todas"') !== false, 'Botão inicial "Tudo" (data-cat="todas") está presente');
assertTeste(strpos($viewContent, 'id="agendamentoBuscaInput"') !== false, 'Barra de pesquisa (#agendamentoBuscaInput) está presente no template');
assertTeste(strpos($viewContent, 'id="agendamentoProdutosGrid"') !== false, 'Grade única de produtos (#agendamentoProdutosGrid) está presente no template');
assertTeste(strpos($viewContent, 'class="col-12 col-md-6 agendamento-prod-col"') !== false, 'Colunas de produtos usam a classe .agendamento-prod-col');
assertTeste(strpos($viewContent, 'data-categoria=') !== false && strpos($viewContent, 'data-nome=') !== false, 'Atributos data-categoria e data-nome estão presentes para filtragem');
assertTeste(strpos($viewContent, 'window.filtrarProdutos = function') !== false, 'Função JavaScript window.filtrarProdutos implementada');
assertTeste(strpos($viewContent, 'id="agendamentoEmptySearch"') !== false, 'Estado vazio para busca sem resultados (#agendamentoEmptySearch) está presente');

// 2. Testa agrupamento e extração de categorias no PortalController
$controllerPath = __DIR__ . '/../app/controllers/PortalController.php';
$controllerContent = file_get_contents($controllerPath);
assertTeste(strpos($controllerContent, "'categorias'             => array_values(\$categoriasCardapio)") !== false, "PortalController repassa a lista ordenada 'categorias' para a view");

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO DOS TESTES DE CATÁLOGO ÚNICO:\n";
echo " Total de asserções: " . ($passou + $falhou) . "\n";
echo " Aprovadas:          $passou\n";
echo " Reprovadas:         $falhou\n";
echo " Taxa de sucesso:    " . round(($passou / ($passou + $falhou)) * 100, 2) . "%\n";
echo "--------------------------------------------------------------------\n\n";

if ($falhou > 0) {
    exit(1);
}
exit(0);
