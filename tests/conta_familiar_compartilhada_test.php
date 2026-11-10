<?php
/**
 * Teste Automatizado: Conta Familiar Compartilhada (Pai e Mãe) e Trava Antiduplicidade
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
echo " TESTES: CONTA FAMILIAR COMPARTILHADA (PAI E MÃE) E TRAVA ANTIDUPLICIDADE\n";
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

// Localiza um dia letivo futuro no calendário
$dataConsumo = date('Y-m-d', strtotime('+3 days'));
while (!CalendarioEscolar::isDiaLetivo($dataConsumo) || date('N', strtotime($dataConsumo)) >= 6) {
    $dataConsumo = date('Y-m-d', strtotime($dataConsumo . ' +1 day'));
}

// Localiza um produto disponível
$produto = Produto::fetchOne("SELECT id, preco_venda, descricao FROM produtos WHERE status_pausa = 'ativo' AND deleted_at IS NULL LIMIT 1");
$prodId = (int)$produto['id'];

// Pai realiza o agendamento
$itens = [
    ['produto_id' => $prodId, 'quantidade' => 1]
];

$pedidoCriado = Pedido::criarPedido($paiId, $alunoId, $dataConsumo, $itens);
assertCondition(!empty($pedidoCriado['id']), "Pai agendou o lanche com sucesso para a data {$dataConsumo}");

// Verifica gravação correta de responsavel_id (família) e autor_responsavel_id (Pai)
$pedidoDb = Pedido::buscarPorId((int)$pedidoCriado['id']);
assertCondition((int)$pedidoDb['responsavel_id'] === $paiId, "Pedido gravado sob o ID da Conta Familiar");
assertCondition((int)$pedidoDb['autor_responsavel_id'] === $paiId, "Autor do pedido registrado como o Pai");

// Mãe consulta os pedidos da família
$pedidosMae = Pedido::listarPorResponsavel($maeId);
$pedidoEncontradoPelaMae = false;
foreach ($pedidosMae as $p) {
    if ((int)$p['id'] === (int)$pedidoCriado['id']) {
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
    // Mãe tenta agendar para o mesmo dependente na mesma data
    Pedido::criarPedido($maeId, $alunoId, $dataConsumo, $itens);
} catch (\DomainException $e) {
    $travaFuncionou = true;
    $msgErro = $e->getMessage();
}

assertCondition($travaFuncionou, "Sistema bloqueou com sucesso a tentativa de pedido duplicado no mesmo dia e turno!");
assertCondition(str_contains($msgErro, 'Já existe um lanche agendado'), "Mensagem informativa amigável exibida: '{$msgErro}'");

// Verifica saldo: não foi debitado duas vezes!
$maeDadosApos = $respModel->find($maeId);
$precoLanche = (float)$produto['preco_venda'];
$saldoEsperado = round(50.00 - $precoLanche, 2);
assertCondition((float)$maeDadosApos['saldo_atual'] === $saldoEsperado, "Saldo da Carteira Familiar debitado apenas 1 vez (R$ {$saldoEsperado})");

// --------------------------------------------------------------------------
// Limpeza Teardown
// --------------------------------------------------------------------------
$pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = :pid")->execute(['pid' => $pedidoCriado['id']]);
$pdo->prepare("DELETE FROM tickets WHERE pedido_id = :pid")->execute(['pid' => $pedidoCriado['id']]);
$pdo->prepare("DELETE FROM pedidos WHERE id = :pid")->execute(['pid' => $pedidoCriado['id']]);
$pdo->prepare("DELETE FROM transacoes_carteira WHERE responsavel_id = :rid")->execute(['rid' => $paiId]);
$pdo->prepare("DELETE FROM alunos WHERE id = :aid")->execute(['aid' => $alunoId]);
$pdo->prepare("DELETE FROM responsaveis WHERE id IN (:pid, :mid)")->execute(['pid' => $paiId, 'mid' => $maeId]);

echo "\n----------------------------------------------------------------------\n";
echo " RESULTADO: {$passedAsserts}/{$totalAsserts} asserções passaram com sucesso!\n";
echo "----------------------------------------------------------------------\n\n";

if ($passedAsserts === $totalAsserts) {
    exit(0);
} else {
    exit(1);
}
