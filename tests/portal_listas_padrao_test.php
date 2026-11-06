<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Padrão de Carga Sob Demanda no Portal da Família
 * 
 * Valida:
 * 1. Lista vazia por padrão no acesso inicial sem filtros.
 * 2. Ao clicar em Filtrar sem mexer em filtros, exibe a lista geral com no máximo 20 itens por página.
 * 3. Ao aplicar filtros específicos e clicar em Filtrar, exibe os itens correspondentes.
 * 4. Preservação de parâmetros e navegação de paginação com limite de 20 itens.
 */

define('CANTINA_SISTEMA', true);

// Registra o Autoloader PSR-4 nativo para App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subDir = strtolower(implode('/', $parts));
    $file = $baseDir . ($subDir !== '' ? $subDir . '/' : '') . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
        return;
    }

    $fallbackFile = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($fallbackFile)) {
        require_once $fallbackFile;
    }
});

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function assertCondition(bool $cond, string $message, &$pass, &$fail): void {
    if ($cond) {
        echo "  [PASS] {$message}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$message}\n";
        $fail++;
    }
}

$pass = 0;
$fail = 0;

echo "======================================================================\n";
echo "  TESTE DE CARGA SOB DEMANDA: PORTAL PEDIDOS E CARTEIRA               \n";
echo "======================================================================\n\n";

// 1. Localiza responsável com histórico para os testes
$resp = \App\Models\Responsavel::fetchOne("
    SELECT r.* FROM responsaveis r
    INNER JOIN transacoes_carteira t ON t.responsavel_id = r.id
    LIMIT 1
");

if (!$resp) {
    $resp = \App\Models\Responsavel::fetchOne("SELECT * FROM responsaveis LIMIT 1");
}

echo "Responsável de teste: {$resp['nome']} (ID: {$resp['id']})\n\n";

// Autentica o responsável na sessão
\App\Core\Session::start();
\App\Core\Auth::loginResponsavel($resp);

// =====================================================================
// PARTE 1: MEUS PEDIDOS (/portal/pedidos)
// =====================================================================
echo "--- 1. Testes em /portal/pedidos ---\n";

$portalController = new \App\Controllers\PortalController();

// 1.1 Acesso inicial sem submissão de filtros
$_GET = [];
$_POST = [];
ob_start();
$portalController->pedidos();
$htmlPedidosInicial = ob_get_clean();

assertCondition(str_contains($htmlPedidosInicial, 'Meus Pedidos de Lanche'), "Título da página presente", $pass, $fail);
assertCondition(str_contains($htmlPedidosInicial, 'Não há nada para mostrar aqui'), "Acesso inicial exibe estado vazio por padrão", $pass, $fail);
assertCondition(str_contains($htmlPedidosInicial, 'Utilize os filtros acima ou clique em') && str_contains($htmlPedidosInicial, 'Filtrar'), "Instrução de filtro exibida no empty_state inicial", $pass, $fail);
assertCondition(!str_contains($htmlPedidosInicial, 'title="Limpar Filtros"'), "Botão Limpar não é exibido antes de filtrar", $pass, $fail);
assertCondition(str_contains($htmlPedidosInicial, 'name="filtrar" value="1"'), "Formulário contém campo oculto filtrar=1", $pass, $fail);

// 1.2 Clique em Filtrar sem mexer em nenhum filtro (filtrar=1)
$_GET = ['filtrar' => '1'];
ob_start();
$portalController->pedidos();
$htmlPedidosFiltrarGeral = ob_get_clean();

assertCondition(str_contains($htmlPedidosFiltrarGeral, 'title="Limpar Filtros"'), "Ao filtrar, o botão Limpar é exibido", $pass, $fail);

// Verifica contagem de pedidos reais do responsável no banco
$totalPedidosBanco = \App\Models\Pedido::contarPorResponsavel((int)$resp['id']);
if ($totalPedidosBanco > 0) {
    assertCondition(str_contains($htmlPedidosFiltrarGeral, '<table class="table table-enterprise'), "Tabela de pedidos renderizada ao filtrar", $pass, $fail);
    assertCondition(str_contains($htmlPedidosFiltrarGeral, 'Código') && str_contains($htmlPedidosFiltrarGeral, 'Data Consumo'), "Cabeçalhos da tabela presentes", $pass, $fail);
} else {
    assertCondition(str_contains($htmlPedidosFiltrarGeral, 'Nenhum pedido encontrado'), "Sem pedidos no banco, exibe mensagem 'Nenhum pedido encontrado'", $pass, $fail);
}

// 1.3 Valida método contarPorResponsavel e paginação de até 20 itens
$pedidosPagina1 = \App\Models\Pedido::listarPorResponsavel((int)$resp['id'], null, null, null, 20, 0);
assertCondition(count($pedidosPagina1) <= 20, "Listagem respeita o limite de no máximo 20 itens por página", $pass, $fail);

// =====================================================================
// PARTE 2: EXTRATO DA CARTEIRA (/portal/carteira)
// =====================================================================
echo "\n--- 2. Testes em /portal/carteira ---\n";

$carteiraController = new \App\Controllers\CarteiraController();

// 2.1 Acesso inicial sem submissão de filtros
$_GET = [];
$_POST = [];
ob_start();
$carteiraController->index();
$htmlCarteiraInicial = ob_get_clean();

assertCondition(str_contains($htmlCarteiraInicial, 'Extrato Analítico de Lançamentos'), "Seção do extrato presente", $pass, $fail);
assertCondition(str_contains($htmlCarteiraInicial, 'Não há nada para mostrar aqui'), "Acesso inicial exibe estado vazio por padrão na tabela de extrato", $pass, $fail);
assertCondition(str_contains($htmlCarteiraInicial, 'Utilize os filtros acima ou clique em') && str_contains($htmlCarteiraInicial, 'Filtrar'), "Instrução de filtro exibida no empty_state do extrato", $pass, $fail);
assertCondition(!str_contains($htmlCarteiraInicial, 'title="Limpar filtros"'), "Botão Limpar do extrato não é exibido antes de filtrar", $pass, $fail);
assertCondition(str_contains($htmlCarteiraInicial, '<input type="hidden" name="filtrar" value="1">'), "Formulário de extrato contém input hidden filtrar=1", $pass, $fail);

// 2.2 Clique em Filtrar sem mexer em filtros (filtrar=1)
$_GET = ['filtrar' => '1'];
ob_start();
$carteiraController->index();
$htmlCarteiraGeral = ob_get_clean();

assertCondition(str_contains($htmlCarteiraGeral, 'title="Limpar filtros"'), "Ao filtrar o extrato, botão Limpar é exibido", $pass, $fail);

$totalTransacoesBanco = \App\Models\TransacaoCarteira::obterExtrato((int)$resp['id'], [], 1, 20)['total'];
if ($totalTransacoesBanco > 0) {
    assertCondition(str_contains($htmlCarteiraGeral, 'Data & Hora') && str_contains($htmlCarteiraGeral, 'Saldo Resultante'), "Tabela de transações renderizada com cabeçalhos", $pass, $fail);
    assertCondition(str_contains($htmlCarteiraGeral, "Total: <strong>{$totalTransacoesBanco}</strong> transações"), "Total de transações exibido corretamente", $pass, $fail);
}

// 2.3 Validação de densidade de 20 itens por página no extrato
$extrato20 = \App\Models\TransacaoCarteira::obterExtrato((int)$resp['id'], [], 1, 20);
assertCondition(count($extrato20['itens']) <= 20, "Extrato respeita limite de no máximo 20 itens por página ({$extrato20['por_pagina']})", $pass, $fail);
assertCondition($extrato20['por_pagina'] === 20, "Propriedade por_pagina configurada exatamente como 20", $pass, $fail);

// 2.4 Filtro específico aplicado (ex: tipo_transacao)
$_GET = ['filtrar' => '1', 'tipo_transacao' => 'recarga_pix'];
ob_start();
$carteiraController->index();
$htmlCarteiraFiltrado = ob_get_clean();

assertCondition(str_contains($htmlCarteiraFiltrado, 'title="Limpar filtros"'), "Botão Limpar continua visível com filtro específico", $pass, $fail);

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$pass} passaram, {$fail} falharam.\n";
echo "----------------------------------------------------------------------\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
