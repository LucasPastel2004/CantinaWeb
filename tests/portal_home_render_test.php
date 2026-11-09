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
    'Pilares com direção vertical correta (flex-direction: column)' => str_contains($html, 'flex-direction: column;'),
    'Contém saldo formatado (R$)' => str_contains($html, 'R$'),
    'Contém atalhos de ação (action-tile-portal)' => str_contains($html, 'action-tile-portal'),
    'Box de dependentes modular (sem h-100 rígido)' => !str_contains($html, 'card-enterprise h-100'),
    'Linha central com alinhamento inicial (align-items-start)' => str_contains($html, 'align-items-start'),
    'Quadro de horários da rotina da cantina removido conforme solicitado' => !str_contains($html, 'Rotina da Cantina') && !str_contains($html, 'Bloqueio de Corte'),
    'Quadro de últimos pedidos removido conforme solicitado' => !str_contains($html, 'Últimos Pedidos & Lanches Agendados'),
    'Badge Área do Responsável removida com sucesso' => !str_contains($html, 'Área do Responsável'),
    'Botões redundantes do topo hero removidos com sucesso' => !str_contains($html, 'Recarregar Saldo Pix') && !str_contains($html, '+ Agendar Lanche</span>'),
    'Badge Cardápio Semanal removida do pilar de agendamento' => !str_contains($html, 'Cardápio Semanal'),
    'Badge Proteção no Balcão removida do pilar de alergias' => !str_contains($html, 'Proteção no Balcão'),
    'Badge de contagem de agendamento removida do pilar de pedidos' => !str_contains($html, 'agendamento(s)</span>'),
    'Botões dos 4 pilares padronizados no azul corporativo (btn-primary)' => substr_count($html, 'btn-primary btn-sm w-100') >= 4,
    'Hover dos atalhos não aplica quadrado azul escuro no ícone' => !str_contains($html, 'background: #1d4ed8;') || !str_contains($html, '.action-tile-portal:hover .action-tile-icon'),
    'Ícone de recarga Pix atualizado para bolsa de moedas com cifrão' => str_contains($html, 'M19 7a4 4') && str_contains($html, 'M9.5 12h5a1.5 1.5 0 0 1 0 3h-5'),
    'Botões da barra superior utilizam classe do azul marinho (btn-portal-header)' => str_contains($html, 'btn-portal-header'),
    'Contém versão de asset 7.2.8' => str_contains($html, 'custom.css?v=7.2.8'),
];

// Teste 2: Renderização direta da view com casos de borda (campos nulos em dependentes e lista modular)
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
    'totalAgendadosAtivos' => 1,
    'dataExtenso'          => 'Sexta-feira, 06 de Novembro de 2026',
    'bloqueadoMed'         => false
];
extract($viewData);
require __DIR__ . '/../app/views/portal/index.php';
$htmlViewBorda = ob_get_clean();

$asserts['Cenário de Borda: View renderizou sem exceções com dependentes nulos'] = strlen($htmlViewBorda) > 1000;
$asserts['Cenário de Borda: Tratou dependente com nome nulo graciosamente'] = str_contains($htmlViewBorda, 'Matrícula: —');
$asserts['Cenário de Borda: Dependente com restrição alimentar renderizado'] = str_contains($htmlViewBorda, 'Contém Glúten');
$asserts['Cenário de Borda: Box modular de dependentes preservou card individual'] = str_contains($htmlViewBorda, 'student-card');

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
