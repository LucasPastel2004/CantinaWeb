<?php
/**
 * Teste Automatizado: Conta Familiar Compartilhada (Pai e Mãe), Edição Cruzada e Trava Antiduplicidade
 * Arquivo: tests/conta_familiar_compartilhada_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';

use App\Core\Session;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\Ticket;
use App\Models\CalendarioEscolar;
use App\Models\TransacaoCarteira;

$totalAsserts = 0;
$passedAsserts = 0;

function assertCondition(bool $cond, string $msg): void {
    global $totalAsserts, $passedAsserts;
    $totalAsserts++;
    if ($cond) {
        $passedAsserts++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
}

echo "\n======================================================================\n";
echo " TESTES COMPLETOS: CONTA FAMILIAR COMPARTILHADA (PAI E MÃE)\n";
echo " EDIÇÃO CRUZADA, CANCELAMENTO, SALDO, EXTRATO E TRAVAS OPERACIONAIS\n";
echo "======================================================================\n\n";

Session::start();
$pdo = \App\Core\Model::getPdo();
$respModel = new Responsavel();

// --------------------------------------------------------------------------
// 1. Criação do Cenário de Teste: Pai (Titular) e Mãe (Co-responsável)
// --------------------------------------------------------------------------
echo "[1] Criando Conta Familiar de Teste: Pai e Mãe com CPFs distintos...\n";

$cpfPai = '11122233399';
$cpfMae = '22233344499';

// Limpeza prévia segura
$antigos = $pdo->query("SELECT id FROM responsaveis WHERE cpf IN ('{$cpfPai}', '{$cpfMae}')")->fetchAll(PDO::FETCH_COLUMN);
if (!empty($antigos)) {
    $idsStr = implode(',', array_map('intval', $antigos));
    $pdo->query("DELETE FROM transacoes_carteira WHERE responsavel_id IN ({$idsStr})");
    $peds = $pdo->query("SELECT id FROM pedidos WHERE responsavel_id IN ({$idsStr})")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($peds)) {
        $pedsStr = implode(',', array_map('intval', $peds));
        $pdo->query("DELETE FROM itens_pedido WHERE pedido_id IN ({$pedsStr})");
        $pdo->query("DELETE FROM tickets WHERE pedido_id IN ({$pedsStr})");
        $pdo->query("DELETE FROM pedidos WHERE id IN ({$pedsStr})");
    }
}
$pdo->prepare("DELETE FROM alunos WHERE matricula = 'MAT-FAM-TESTE'")->execute();
$pdo->prepare("DELETE FROM responsaveis WHERE cpf IN ('{$cpfPai}', '{$cpfMae}')")->execute();

// 1. Cria o Pai como Titular Financeiro
$resPai = Responsavel::localizarOuCriarPorCpf([
    'cpf'        => $cpfPai,
    'nome'       => 'Carlos Pai da Silva',
    'whatsapp'   => '(62) 99111-2222',
    'email'      => 'carlos.pai@teste.com',
    'titular_id' => null,
    'parentesco' => 'titular'
]);
$paiId = (int)$resPai['id'];
$respModel->updatePassword($paiId, 'senhaPai123');
$respModel->aceitarTermos($paiId);

// Adiciona saldo na carteira do Pai (Titular)
TransacaoCarteira::creditar($paiId, 50.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Recarga Inicial Pai');

// 2. Cria a Mãe vinculada à Conta Familiar do Pai
$resMae = Responsavel::localizarOuCriarPorCpf([
    'cpf'        => $cpfMae,
    'nome'       => 'Mariana Mae da Silva',
    'whatsapp'   => '(62) 99333-4444',
    'email'      => 'mariana.mae@teste.com',
    'titular_id' => $paiId,
    'parentesco' => 'pedagogico'
]);
$maeId = (int)$resMae['id'];
$respModel->updatePassword($maeId, 'senhaMae456');
$respModel->aceitarTermos($maeId);

// 3. Cria um Filho vinculado ao Titular da Conta Familiar ($paiId)
$turma = Turma::fetchOne("SELECT id, turno FROM turmas WHERE deleted_at IS NULL LIMIT 1");
$turmaId = (int)$turma['id'];
$turnoAluno = $turma['turno'] ?? 'matutino';

$resAluno = Aluno::localizarOuCriarPorMatricula([
    'matricula'      => 'MAT-FAM-TESTE',
    'nome'           => 'Lucas Silva Teste',
    'turma_id'       => $turmaId,
    'responsavel_id' => $paiId,
    'turno'          => $turnoAluno,
    'ativo'          => 1
]);
$alunoId = (int)$resAluno['id'];

assertCondition($paiId > 0 && $maeId > 0, "Pai e Mãe cadastrados com sucesso com IDs distintos");
assertCondition($alunoId > 0, "Filho cadastrado e vinculado à família");

// --------------------------------------------------------------------------
// 2. Teste de Vínculo Familiar e Resolução da Conta Compartilhada
// --------------------------------------------------------------------------
echo "\n[2] Testando Compartilhamento de Conta Familiar...\n";

assertCondition($respModel->getContaFamiliarId($paiId) === $paiId, "ID da Conta Familiar do Pai aponta para ele mesmo");
assertCondition($respModel->getContaFamiliarId($maeId) === $paiId, "ID da Conta Familiar da Mãe aponta para o Titular (Pai)");

$membros = $respModel->getMembrosFamilia($maeId);
assertCondition(count($membros) === 2, "getMembrosFamilia retorna ambos os responsáveis (Pai e Mãe)");

$dependentesPai = $respModel->getDependentes($paiId);
$dependentesMae = $respModel->getDependentes($maeId);
assertCondition(count($dependentesPai) === count($dependentesMae), "Pai e Mãe veem a mesma quantidade de dependentes");
assertCondition(($dependentesPai[0]['id'] ?? 0) === $alunoId, "Pai vê o filho Lucas");
assertCondition(($dependentesMae[0]['id'] ?? 0) === $alunoId, "Mãe vê o mesmo filho Lucas");

$paiDados = $respModel->find($paiId);
$maeDados = $respModel->find($maeId);
assertCondition((float)$paiDados['saldo_atual'] === 50.00, "Saldo do Pai é R$ 50,00");
assertCondition((float)$maeDados['saldo_atual'] === 50.00, "Saldo da Mãe herda automaticamente os R$ 50,00 da Carteira Familiar");

// --------------------------------------------------------------------------
// 3. Teste de Agendamento pelo Pai e Visibilidade em Tempo Real para a Mãe
// --------------------------------------------------------------------------
echo "\n[3] Testando Agendamento pelo Pai e Visibilidade para a Mãe...\n";

// Localiza dois dias letivos válidos na próxima semana letiva para execução determinística em qualquer dia/hora
$diasProximaSemana = CalendarioEscolar::obterDiasSemanaLetiva('seguinte');
$diasCorrentes = [];
foreach ($diasProximaSemana as $infoDia) {
    if (!empty($infoDia['is_letivo'])) {
        $diasCorrentes[] = $infoDia['data'];
    }
}
if (count($diasCorrentes) < 2) {
    for ($offset = 7; $offset <= 21 && count($diasCorrentes) < 2; $offset++) {
        $d = date('Y-m-d', strtotime("+{$offset} days"));
        if (CalendarioEscolar::isDiaLetivo($d) && (int)date('N', strtotime($d)) <= 5 && !in_array($d, $diasCorrentes, true)) {
            $diasCorrentes[] = $d;
        }
    }
}
$dataConsumo1 = $diasCorrentes[0];
$dataConsumo2 = $diasCorrentes[1];
$agoraSimulado = new \DateTimeImmutable($dataConsumo1 . ' 07:00:00');

// Localiza dois produtos disponíveis
$produtos = Produto::fetchAll("SELECT id, preco_venda, descricao FROM produtos WHERE status_pausa = 'ativo' AND deleted_at IS NULL LIMIT 2");
$prod1 = $produtos[0];
$prod2 = $produtos[1];

$prod1Id = (int)$prod1['id'];
$prod1Preco = (float)$prod1['preco_venda'];
$prod2Id = (int)$prod2['id'];
$prod2Preco = (float)$prod2['preco_venda'];

// Pai realiza o agendamento de 1x Produto 1
$itensPai = [
    ['produto_id' => $prod1Id, 'quantidade' => 1]
];

$pedidoCriado = Pedido::criarPedido($paiId, $alunoId, $dataConsumo1, $itensPai, $agoraSimulado);
$pedidoId = (int)$pedidoCriado['id'];
assertCondition($pedidoId > 0, "Pai agendou o lanche com sucesso para {$dataConsumo1} (R$ {$prod1Preco})");

// Verifica gravação correta de responsavel_id (família) e autor_responsavel_id (Pai)
$pedidoDb = Pedido::buscarPorId($pedidoId);
assertCondition((int)$pedidoDb['responsavel_id'] === $paiId, "Pedido gravado sob o ID da Conta Familiar");
assertCondition((int)$pedidoDb['autor_responsavel_id'] === $paiId, "Autor do pedido registrado como o Pai");

// Mãe consulta os pedidos da família
$pedidosMae = Pedido::listarPorResponsavel($maeId);
$pedidoEncontradoPelaMae = false;
foreach ($pedidosMae as $p) {
    if ((int)$p['id'] === $pedidoId) {
        $pedidoEncontradoPelaMae = true;
        break;
    }
}
assertCondition($pedidoEncontradoPelaMae, "Mãe visualiza o pedido agendado pelo Pai imediatamente!");

// --------------------------------------------------------------------------
// 4. Teste da Trava Antiduplicidade: Mãe tenta agendar para o mesmo dia e turno
// --------------------------------------------------------------------------
echo "\n[4] Testando Trava Antiduplicidade: Mãe tenta agendar no mesmo dia...\n";

$travaFuncionou = false;
$msgErro = '';

try {
    Pedido::criarPedido($maeId, $alunoId, $dataConsumo1, $itensPai, $agoraSimulado);
} catch (\DomainException $e) {
    $travaFuncionou = true;
    $msgErro = $e->getMessage();
}

assertCondition($travaFuncionou, "Sistema bloqueou com sucesso a tentativa de pedido duplicado no mesmo dia e turno!");
assertCondition(str_contains($msgErro, 'Já existe um lanche agendado'), "Mensagem informativa amigável exibida");

$saldoAposAgendamento = round(50.00 - $prod1Preco, 2);
assertCondition(Responsavel::consultarSaldo($maeId) === $saldoAposAgendamento, "Saldo da Mãe conferido: R$ {$saldoAposAgendamento}");

// --------------------------------------------------------------------------
// 5. Teste de Edição Cruzada: Mãe edita o pedido do Pai e ADICIONA item (+ R$)
// --------------------------------------------------------------------------
echo "\n[5] Testando Edição Cruzada pela Mãe: Adicionando item ao pedido do Pai...\n";

// Mãe adiciona o Produto 2 ao pedido feito originalmente pelo Pai
$novosItensMae = [
    ['produto_id' => $prod1Id, 'quantidade' => 1],
    ['produto_id' => $prod2Id, 'quantidade' => 1]
];

$resEdicaoMae = Pedido::editarPedido($pedidoId, $maeId, $novosItensMae, $agoraSimulado);
assertCondition(!empty($resEdicaoMae['codigo_pedido']), "Mãe editou o pedido do Pai com sucesso!");

$novoTotalEsperado = round($prod1Preco + $prod2Preco, 2);
assertCondition((float)$resEdicaoMae['novo_total'] === $novoTotalEsperado, "Novo total do pedido recalculado para R$ {$novoTotalEsperado}");
assertCondition((float)$resEdicaoMae['diferenca'] === $prod2Preco, "Diferença calculada com exatidão: +R$ {$prod2Preco}");

// Verifica saldo debitado da carteira familiar
$saldoAposEdicao = round(50.00 - $novoTotalEsperado, 2);
assertCondition(Responsavel::consultarSaldo($paiId) === $saldoAposEdicao, "Saldo do Pai reflete débito da edição feita pela Mãe: R$ {$saldoAposEdicao}");
assertCondition(Responsavel::consultarSaldo($maeId) === $saldoAposEdicao, "Saldo da Mãe reflete o mesmo valor exato: R$ {$saldoAposEdicao}");

// Verifica autor da edição registrado como a Mãe
$pedidoAtualizado = Pedido::buscarDetalhado($pedidoId, $paiId);
assertCondition((int)$pedidoAtualizado['autor_responsavel_id'] === $maeId, "Autor da alteração registrado no banco como a Mãe");
assertCondition(count($pedidoAtualizado['itens']) === 2, "Pai visualiza os 2 itens atualizados no pedido");

// --------------------------------------------------------------------------
// 6. Teste de Edição Cruzada: Pai edita pedido e REMOVE item (- R$, Estorno)
// --------------------------------------------------------------------------
echo "\n[6] Testando Edição Cruzada pelo Pai: Removendo item e gerando estorno...\n";

// Pai remove o Produto 2 (volta para apenas Produto 1)
$itensVoltaPai = [
    ['produto_id' => $prod1Id, 'quantidade' => 1]
];

$resEdicaoPai = Pedido::editarPedido($pedidoId, $paiId, $itensVoltaPai, $agoraSimulado);
assertCondition((float)$resEdicaoPai['novo_total'] === $prod1Preco, "Total voltou para R$ {$prod1Preco}");
assertCondition((float)$resEdicaoPai['diferenca'] === -$prod2Preco, "Diferença negativa gerou estorno de R$ {$prod2Preco}");

// Saldo familiar deve voltar para R$ 50 - prod1Preco
assertCondition(Responsavel::consultarSaldo($maeId) === $saldoAposAgendamento, "Saldo da Mãe recomposto após estorno de edição: R$ {$saldoAposAgendamento}");
assertCondition(Responsavel::consultarSaldo($paiId) === $saldoAposAgendamento, "Saldo do Pai recomposto após estorno de edição: R$ {$saldoAposAgendamento}");

// --------------------------------------------------------------------------
// 7. Teste de Cancelamento Cruzado: Mãe CANCELA o pedido feito pelo Pai
// --------------------------------------------------------------------------
echo "\n[7] Testando Cancelamento Cruzado pela Mãe: Estorno integral...\n";

$resCancelMae = Pedido::cancelarPedido($pedidoId, $maeId, $agoraSimulado);
assertCondition($resCancelMae['status'] === Pedido::STATUS_CANCELADO, "Mãe cancelou o pedido com sucesso!");
assertCondition((float)$resCancelMae['valor_estorno'] === $prod1Preco, "Valor estornado de 100% (R$ {$prod1Preco})");

// Verifica se os tickets foram cancelados
$tickets = Ticket::fetchAll("SELECT status FROM tickets WHERE pedido_id = :pid", ['pid' => $pedidoId]);
$todosTicketsCancelados = true;
foreach ($tickets as $tk) {
    if ($tk['status'] !== 'cancelado') {
        $todosTicketsCancelados = false;
        break;
    }
}
assertCondition($todosTicketsCancelados && count($tickets) > 0, "Todos os tickets térmicos do pedido foram cancelados");

// Saldo familiar deve voltar para os R$ 50,00 integrais
assertCondition(Responsavel::consultarSaldo($paiId) === 50.00, "Saldo do Pai voltou a R$ 50,00 integrais");
assertCondition(Responsavel::consultarSaldo($maeId) === 50.00, "Saldo da Mãe voltou a R$ 50,00 integrais");

// --------------------------------------------------------------------------
// 8. Teste de Agendamento pela Mãe e Cancelamento pelo Pai (Fluxo Inverso)
// --------------------------------------------------------------------------
echo "\n[8] Testando Ciclo Inverso: Mãe agenda novo pedido e Pai cancela...\n";

$agoraSimulado2 = new \DateTimeImmutable($dataConsumo2 . ' 07:00:00');
$pedidoMae = Pedido::criarPedido($maeId, $alunoId, $dataConsumo2, $itensPai, $agoraSimulado2);
$pedidoMaeId = (int)$pedidoMae['id'];
assertCondition($pedidoMaeId > 0, "Mãe agendou pedido com sucesso para {$dataConsumo2}");

// Pai busca detalhado do pedido agendado pela Mãe
$detalhesPai = Pedido::buscarDetalhado($pedidoMaeId, $paiId);
assertCondition(!empty($detalhesPai), "Pai conseguiu buscar os detalhes do pedido da Mãe");
assertCondition((int)$detalhesPai['autor_responsavel_id'] === $maeId, "Autor registrado é a Mãe");

// Pai cancela o pedido feito pela Mãe
$resCancelPai = Pedido::cancelarPedido($pedidoMaeId, $paiId, $agoraSimulado2);
assertCondition($resCancelPai['status'] === Pedido::STATUS_CANCELADO, "Pai cancelou o pedido feito pela Mãe com sucesso!");
assertCondition(Responsavel::consultarSaldo($maeId) === 50.00, "Saldo familiar reconstituído em R$ 50,00 após cancelamento pelo Pai");

// --------------------------------------------------------------------------
// 9. Teste de Bloqueio MED Compartilhado (Contestação Bancária Familiar)
// --------------------------------------------------------------------------
echo "\n[9] Testando Bloqueio MED Compartilhado para a Família...\n";

// Aplica bloqueio MED pelo ID da Mãe
Responsavel::definirBloqueioMed($maeId, true);
assertCondition(Responsavel::isBloqueadoMed($maeId) === true, "Mãe está com bloqueio MED ativo");
assertCondition(Responsavel::isBloqueadoMed($paiId) === true, "Pai herda o bloqueio MED da conta familiar");

// Desbloqueia MED
Responsavel::definirBloqueioMed($paiId, false);
assertCondition(Responsavel::isBloqueadoMed($maeId) === false, "Mãe desbloqueada com sucesso");
assertCondition(Responsavel::isBloqueadoMed($paiId) === false, "Pai desbloqueado com sucesso");

// --------------------------------------------------------------------------
// Limpeza Teardown
// --------------------------------------------------------------------------
$pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id IN (:p1, :p2)")->execute(['p1' => $pedidoId, 'p2' => $pedidoMaeId]);
$pdo->prepare("DELETE FROM tickets WHERE pedido_id IN (:p1, :p2)")->execute(['p1' => $pedidoId, 'p2' => $pedidoMaeId]);
$pdo->prepare("DELETE FROM pedidos WHERE id IN (:p1, :p2)")->execute(['p1' => $pedidoId, 'p2' => $pedidoMaeId]);
$pdo->prepare("DELETE FROM transacoes_carteira WHERE responsavel_id IN (:pid, :mid)")->execute(['pid' => $paiId, 'mid' => $maeId]);
$pdo->prepare("DELETE FROM alunos WHERE id = :aid")->execute(['aid' => $alunoId]);
$pdo->prepare("DELETE FROM responsaveis WHERE id IN (:pid, :mid)")->execute(['pid' => $paiId, 'mid' => $maeId]);

echo "\n----------------------------------------------------------------------\n";
echo " RESULTADO FINAL: {$passedAsserts}/{$totalAsserts} asserções passaram com sucesso!\n";
echo "----------------------------------------------------------------------\n\n";

if ($passedAsserts === $totalAsserts) {
    exit(0);
} else {
    exit(1);
}
