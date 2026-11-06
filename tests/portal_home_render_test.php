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
require_once __DIR__ . '/../app/core/ErrorHandler.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\ErrorHandler;
use App\Controllers\PortalController;

// Ativa ErrorHandler para garantir que qualquer Notice/Warning gere falha se houver
ErrorHandler::register(false);

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
    'Contém versão de asset 7.2.3' => str_contains($html, 'custom.css?v=7.2.3'),
];

// Teste 2: Renderização direta da view com casos de borda (pedidos com itens, itens apenas com produto_descricao, campos nulos em dependentes)
ob_start();
$viewData = [
    'baseUrl'              => '',
    'usuario'              => ['id' => 999, 'nome' => 'ADRIANE DA SILVA MARTINS', 'saldo' => 10.00],
    'responsavelNome'      => 'ADRIANE DA SILVA MARTINS',
    'saldoAtual'           => 10.00,
    'dependentes'          => [
        [
            'id'                => 101,
            'nome'              => 'JULIA MARTINS GARCIA',
            'matricula'         => '00002092',
            'nome_turma'        => '5º Ano A',
            'turno_turma'       => 'matutino',
            'total_restricoes'  => 1,
            'restricoes'        => [
                ['id' => 1, 'nome' => 'Glúten', 'nome_exibicao' => 'Contém Glúten']
            ]
        ],
        [
            'id'                => 102,
            'nome'              => null, // Testa nulo
            'matricula'         => null, // Testa nulo
            'nome_turma'        => null,
            'turno_turma'       => null,
            'total_restricoes'  => 0,
            'restricoes'        => []
        ]
    ],
    'ultimosPedidos'       => [
        [
            'id'            => 501,
            'data_consumo'  => '2026-11-06',
            'turno_consumo' => 'matutino',
            'aluno_nome'    => 'JULIA MARTINS GARCIA',
            'valor_total'   => 12.50,
            'status'        => 'agendado',
            'itens'         => [
                ['quantidade' => 1, 'produto_descricao' => 'Pão de Queijo Tradicional', 'preco_unitario' => 6.50],
                ['quantidade' => 1, 'produto_nome' => 'Suco de Laranja 300ml', 'preco_unitario' => 6.00],
            ]
        ]
    ],
    'totalAgendadosAtivos' => 1,
    'dataExtenso'          => 'Sexta-feira, 06 de Novembro de 2026',
    'bloqueadoMed'         => false
];
extract($viewData);
require __DIR__ . '/../app/views/portal/index.php';
$htmlViewBorda = ob_get_clean();

$asserts['Cenário de Borda: View renderizou sem exceções com itens e nulos'] = strlen($htmlViewBorda) > 1000;
$asserts['Cenário de Borda: Contém item por produto_descricao (Pão de Queijo)'] = str_contains($htmlViewBorda, 'Pão de Queijo Tradicional');
$asserts['Cenário de Borda: Contém item por produto_nome (Suco de Laranja)'] = str_contains($htmlViewBorda, 'Suco de Laranja 300ml');
$asserts['Cenário de Borda: Tratou dependente com nome nulo graciosamente'] = str_contains($htmlViewBorda, 'Matrícula: —');

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
