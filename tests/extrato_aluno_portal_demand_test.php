<?php
/**
 * Teste Automatizado de Carga Sob Demanda e Emissão Nativa no Extrato do Aluno (/portal/extrato)
 */

declare(strict_types=1);

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';
require_once __DIR__ . '/../app/services/PdfService.php';

use App\Core\Model;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Controllers\PortalController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = Model::getPdo();

echo "======================================================================\n";
echo "  TESTE: EXTRATO DO ALUNO NO PORTAL DA FAMÍLIA (CARGA SOB DEMANDA)    \n";
echo "======================================================================\n\n";

// Localiza um responsável que tenha dependentes e movimentações
$stmt = $pdo->query("
    SELECT r.* 
    FROM responsaveis r 
    INNER JOIN alunos a ON a.responsavel_id = r.id 
    LIMIT 1
");
$resp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resp) {
    echo "ERRO: Nenhum responsável encontrado no banco de dados.\n";
    exit(1);
}

$respId = (int)$resp['id'];
$dependentes = Aluno::listarPorResponsavel($respId, false);
$alunoId = (int)$dependentes[0]['id'];

// Autentica o responsável na sessão oficial
Session::start();
Auth::loginResponsavel($resp);

$passou = 0;
$total = 0;

function assertCustom(bool $cond, string $msg, &$passou, &$total): void {
    $total++;
    if ($cond) {
        $passou++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
}

// ---------------------------------------------------------
// 1. Teste de Acesso Inicial: Lista começa inicialmente vazia
// ---------------------------------------------------------
echo "--- 1. Acesso Inicial a /portal/extrato (Sem submissão de filtros) ---\n";
$_GET = ['aluno_id' => $alunoId];
$_POST = [];

$controller = new PortalController();
ob_start();
$controller->extrato();
$htmlInicial = ob_get_clean();

assertCustom(str_contains($htmlInicial, 'Extrato Analítico do Aluno'), "Título do módulo presente", $passou, $total);
assertCustom(str_contains($htmlInicial, 'Imprimir / Salvar PDF'), "Botão único 'Imprimir / Salvar PDF' presente", $passou, $total);
assertCustom(!str_contains($htmlInicial, 'Baixar Extrato Oficial (PDF)'), "Botão Gotenberg 'Baixar Extrato Oficial (PDF)' ausente", $passou, $total);
assertCustom(!str_contains($htmlInicial, 'Visualizar / Imprimir (A4)'), "Botão legado 'Visualizar / Imprimir (A4)' ausente", $passou, $total);
assertCustom(!str_contains($htmlInicial, 'stat-strip'), "Faixa stat-strip com X verde removida com sucesso", $passou, $total);
assertCustom(str_contains($htmlInicial, 'Não há nada para mostrar aqui'), "Lista começa inicialmente vazia exibindo empty_state.php", $passou, $total);
assertCustom(str_contains($htmlInicial, 'Filtrar Período'), "Botão azul 'Filtrar Período' presente", $passou, $total);
assertCustom(!str_contains($htmlInicial, 'title="Limpar filtros e retornar à visualização inicial"'), "Botão 'Limpar' ausente no estado inicial não filtrado", $passou, $total);
assertCustom(str_contains($htmlInicial, '0 lançamento(s)'), "Contagem de lançamentos indica 0 no estado inicial", $passou, $total);

// ---------------------------------------------------------
// 2. Teste de Filtro Geral: Clicou em Filtrar mantendo os gerais iniciais
// ---------------------------------------------------------
echo "\n--- 2. Submissão com Filtro Geral (filtrar=1 & filtro_geral=1) ---\n";
$_GET = [
    'aluno_id'     => $alunoId,
    'filtrar'      => '1',
    'filtro_geral' => '1'
];

ob_start();
$controller->extrato();
$htmlGeral = ob_get_clean();

assertCustom(str_contains($htmlGeral, 'title="Limpar filtros e retornar à visualização inicial"'), "Botão 'Limpar' é exibido ao filtrar", $passou, $total);
assertCustom(str_contains($htmlGeral, 'Todo o Histórico (Geral)'), "Identificação do período indica lista geral de tudo", $passou, $total);
assertCustom(str_contains($htmlGeral, 'table-data-grid'), "Tabela utiliza a classe de alta densidade table-data-grid", $passou, $total);

// ---------------------------------------------------------
// 3. Teste de Filtro Específico: Mexeu nos filtros (filtro_geral=0)
// ---------------------------------------------------------
echo "\n--- 3. Submissão com Período Específico (filtro_geral=0) ---\n";
$_GET = [
    'aluno_id'     => $alunoId,
    'filtrar'      => '1',
    'filtro_geral' => '0',
    'data_inicio'  => '2026-01-01',
    'data_fim'     => '2026-01-31'
];

ob_start();
$controller->extrato();
$htmlEspecifico = ob_get_clean();

assertCustom(str_contains($htmlEspecifico, '01/01/2026</strong> a <strong class="font-tabular text-dark">31/01/2026'), "Período filtrado exibido exatamente como definido", $passou, $total);
assertCustom(str_contains($htmlEspecifico, 'title="Limpar filtros e retornar à visualização inicial"'), "Botão 'Limpar' permanece ativo", $passou, $total);

// ---------------------------------------------------------
// 4. Teste do Espelho Nativo de Impressão A4 (/portal/extrato/imprimir)
// ---------------------------------------------------------
echo "\n--- 4. Espelho Nativo de Impressão A4 (/portal/extrato/imprimir) ---\n";
$_GET = [
    'aluno_id'     => $alunoId,
    'filtro_geral' => '1'
];

ob_start();
try {
    $controller->extratoImprimir();
} catch (\Throwable $e) {
    // capturado caso faça exit
}
$htmlImprimir = ob_get_clean();

assertCustom(str_contains($htmlImprimir, 'COLÉGIO MONTEIRO LOBATO'), "Cabeçalho escolar timbrado presente no espelho A4", $passou, $total);
assertCustom(str_contains($htmlImprimir, '🖨 Imprimir / Salvar PDF'), "Botão nativo window.print() '🖨 Imprimir / Salvar PDF' presente no espelho A4", $passou, $total);
assertCustom(str_contains($htmlImprimir, 'btn-zoom'), "Controles de zoom para conferência presentes", $passou, $total);
assertCustom(str_contains($htmlImprimir, 'table-doc-excel'), "Tabela no padrão Excel corporativo presente", $passou, $total);

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$passou} passaram, " . ($total - $passou) . " falharam de um total de {$total}.\n";
echo "----------------------------------------------------------------------\n";

if ($passou === $total) {
    exit(0);
}
exit(1);
