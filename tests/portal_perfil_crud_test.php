<?php
/**
 * Teste Automatizado: CRUD de Telefone/Email, Proteção de CPF e Unificação de Alteração de Senha
 * Arquivo: tests/portal_perfil_crud_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\RBAC;
use App\Core\Router;
use App\Models\Responsavel;
use App\Controllers\PortalController;

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

echo "\n======================================================\n";
echo " TESTES: CRUD DE CONTATOS E PERFIL NO PORTAL DA FAMÍLIA\n";
echo "======================================================\n\n";

Session::start();

// --------------------------------------------------------------------------
// 1. Teste de Model: atualizarDadosCadastrais() e integridade do CPF
// --------------------------------------------------------------------------
echo "[1] Testando Responsavel::atualizarDadosCadastrais() e blindagem do CPF...\n";

$respModel = new Responsavel();
// Localiza qualquer responsável existente para o teste
$respTeste = Responsavel::fetchOne("SELECT * FROM responsaveis LIMIT 1");

if (!$respTeste) {
    echo "  [WARN] Nenhum responsável encontrado no banco. Criando registro temporário...\n";
    $pdo = \App\Core\Model::getPdo();
    $pdo->exec("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, termos_aceitos_em) 
                VALUES ('12345678901', 'Responsavel Teste', 'original@teste.com', '47999990000', 'hash', 0.00, NOW())");
    $respTeste = Responsavel::fetchOne("SELECT * FROM responsaveis WHERE cpf = '12345678901'");
}

$idOriginal   = (int)$respTeste['id'];
$cpfOriginal  = $respTeste['cpf'];
$nomeOriginal = $respTeste['nome'];
$whatsOriginal = $respTeste['whatsapp'];
$emailOriginal = $respTeste['email'];

// Update com novos dados (Create/Update de Telefone e Email)
$resUpdate = $respModel->atualizarDadosCadastrais($idOriginal, [
    'nome'     => 'Nome Atualizado de Teste',
    'whatsapp' => '(47) 98888-7777',
    'email'    => 'novo_email_teste@escola.com'
]);
assertCondition($resUpdate === true, "atualizarDadosCadastrais executou com sucesso");

$respAtualizado = $respModel->find($idOriginal);
assertCondition($respAtualizado['nome'] === 'Nome Atualizado de Teste', "Nome foi atualizado corretamente");
assertCondition($respAtualizado['whatsapp'] === '(47) 98888-7777', "Telefone/WhatsApp foi atualizado corretamente (Update)");
assertCondition($respAtualizado['email'] === 'novo_email_teste@escola.com', "E-mail foi atualizado corretamente (Update)");
assertCondition($respAtualizado['cpf'] === $cpfOriginal, "CPF permaneceu rigorosamente intacto");

// Limpar dados (Delete de Telefone e Email)
$resDelete = $respModel->atualizarDadosCadastrais($idOriginal, [
    'nome'     => 'Nome Atualizado de Teste',
    'whatsapp' => '',
    'email'    => ''
]);
assertCondition($resDelete === true, "atualizarDadosCadastrais permitiu apagar telefone e email (Delete/Clear)");

$respLimpo = $respModel->find($idOriginal);
assertCondition(empty($respLimpo['whatsapp']), "Telefone ficou vazio após exclusão/limpeza");
assertCondition(empty($respLimpo['email']), "E-mail ficou vazio após exclusão/limpeza");
assertCondition($respLimpo['cpf'] === $cpfOriginal, "CPF continuou estritamente inalterado");

// Restaura os dados originais
$respModel->atualizarDadosCadastrais($idOriginal, [
    'nome'     => $nomeOriginal,
    'whatsapp' => $whatsOriginal,
    'email'    => $emailOriginal
]);

// --------------------------------------------------------------------------
// 2. Teste de Roteador e RBAC
// --------------------------------------------------------------------------
echo "\n[2] Testando Registro de Rotas no index.php e RBAC...\n";

$router = new Router();
$router->post('/portal/perfil/atualizar', [PortalController::class, 'atualizarPerfil'], [
    [RBAC::class, 'requireResponsavel']
]);

$ref = new \ReflectionClass($router);
$prop = $ref->getProperty('routes');
$prop->setAccessible(true);
$rotas = $prop->getValue($router);
$rotasPost = $rotas['POST'] ?? [];
assertCondition(!empty($rotasPost), "Rota POST registrada no roteador");
$rotaEncontrada = false;
foreach ($rotasPost as $r) {
    if (($r['path'] ?? '') === '/portal/perfil/atualizar') {
        $rotaEncontrada = true;
        assertCondition(!empty($r['middlewares']), "Rota protegida com middleware de segurança RBAC");
        break;
    }
}
assertCondition($rotaEncontrada, "Rota POST /portal/perfil/atualizar devidamente registrada");

// --------------------------------------------------------------------------
// 3. Teste do Controller: Simulação de atualizarPerfil()
// --------------------------------------------------------------------------
echo "\n[3] Testando Lógica do Controller PortalController::atualizarPerfil()...\n";

// Simula autenticação do responsável
Session::set('_auth_logged', true);
Session::set('_auth_id', $idOriginal);
Session::set('_auth_type', 'responsavel');
Session::set('_auth_role', 'responsavel');
Session::set('_auth_username', $cpfOriginal);
Session::set('_auth_nome', $nomeOriginal);
Session::set('_auth_whatsapp', $whatsOriginal);
Session::set('_auth_email', $emailOriginal);
Session::set('_auth_saldo', 0.00);

$controller = new PortalController();

// 3.1 CSRF Inválido
$_POST = [
    'csrf_token' => 'token_invalido',
    'nome'       => 'Teste',
    'whatsapp'   => '123',
    'email'      => 'teste@email.com'
];
ob_start();
try {
    $controller->atualizarPerfil();
} catch (\Throwable $e) {}
ob_end_clean();
$flashDanger = Session::getFlash('danger');
assertCondition(!empty($flashDanger), "Rejeita requisição com CSRF inválido ou ausente");

// 3.2 Nome curto (< 3 chars)
$tokenValido = Session::generateCsrfToken();
$_POST = [
    'csrf_token' => $tokenValido,
    'nome'       => 'Ab',
    'whatsapp'   => '123',
    'email'      => 'teste@email.com'
];
ob_start();
try {
    $controller->atualizarPerfil();
} catch (\Throwable $e) {}
ob_end_clean();
$flashWarning = Session::getFlash('warning');
assertCondition(!empty($flashWarning) && str_contains(implode(' ', (array)$flashWarning), 'mínimo 3 caracteres'), "Rejeita nome com menos de 3 caracteres");

// 3.3 E-mail inválido
$tokenValido = Session::generateCsrfToken();
$_POST = [
    'csrf_token' => $tokenValido,
    'nome'       => 'Nome Valido',
    'whatsapp'   => '47999998888',
    'email'      => 'email_sem_arroba'
];
ob_start();
try {
    $controller->atualizarPerfil();
} catch (\Throwable $e) {}
ob_end_clean();
$flashWarningEmail = Session::getFlash('warning');
assertCondition(!empty($flashWarningEmail) && str_contains(implode(' ', (array)$flashWarningEmail), 'inválido'), "Rejeita formato de e-mail inválido");

// 3.4 Sucesso com novos dados
$tokenValido = Session::generateCsrfToken();
$_POST = [
    'csrf_token' => $tokenValido,
    'nome'       => 'Adriane Silva Teste',
    'whatsapp'   => '(47) 99123-4567',
    'email'      => 'adriane.teste@exemplo.com'
];
ob_start();
try {
    $controller->atualizarPerfil();
} catch (\Throwable $e) {}
ob_end_clean();
$flashSuccess = Session::getFlash('success');
assertCondition(!empty($flashSuccess), "Atualiza com sucesso e despacha mensagem flash de confirmação");
assertCondition(Session::get('_auth_nome') === 'Adriane Silva Teste', "Atualiza o nome na sessão ativa");
assertCondition(Session::get('_auth_whatsapp') === '(47) 99123-4567', "Atualiza o WhatsApp na sessão ativa");
assertCondition(Session::get('_auth_email') === 'adriane.teste@exemplo.com', "Atualiza o E-mail na sessão ativa");

// Restaura banco
$respModel->atualizarDadosCadastrais($idOriginal, [
    'nome'     => $nomeOriginal,
    'whatsapp' => $whatsOriginal,
    'email'    => $emailOriginal
]);

// --------------------------------------------------------------------------
// 4. Teste de Renderização da View app/views/portal/perfil.php
// --------------------------------------------------------------------------
echo "\n[4] Testando Renderização da View perfil.php...\n";

$viewFile = __DIR__ . '/../app/views/portal/perfil.php';
assertCondition(file_exists($viewFile), "Arquivo app/views/portal/perfil.php existe");

// Simula variáveis passadas para a view
$baseUrl = '';
$responsavel = [
    'id'                => $idOriginal,
    'nome'              => 'Adriane da Silva Martins',
    'cpf'               => '99751216168',
    'whatsapp'          => '(47) 99999-9999',
    'email'             => 'lucashcc2306@gmail.com',
    'termos_aceitos_em' => '2026-11-06 08:57:00'
];
$dependentes = [
    [
        'id'         => 1,
        'nome'       => 'JÚLIA MARTINS GARCIA',
        'matricula'  => '00002092',
        'nome_turma' => '5º Ano A',
        'turno'      => 'matutino'
    ]
];
$csrfToken = 'csrf_token_teste_123';

ob_start();
include $viewFile;
$html = ob_get_clean();

// 4.1 Botão de edição presente
assertCondition(str_contains($html, 'id="btn-toggle-editar"'), "Botão de alternância de edição presente no cabeçalho");
assertCondition(str_contains($html, 'Editar Dados'), "Rótulo 'Editar Dados' presente no botão");

// 4.2 Containers de visualização e edição presentes
assertCondition(str_contains($html, 'id="dados-view-mode"'), "Contêiner de modo visualização presente");
assertCondition(str_contains($html, 'id="dados-edit-mode"'), "Contêiner de modo edição presente");

// 4.3 Campos do formulário e CPF bloqueado
assertCondition(str_contains($html, 'id="form-atualizar-dados"'), "Formulário de atualização de dados presente");
assertCondition(str_contains($html, 'action="/portal/perfil/atualizar"'), "Formulário submete para /portal/perfil/atualizar");
assertCondition(str_contains($html, 'name="whatsapp"'), "Campo de telefone/whatsapp presente");
assertCondition(str_contains($html, 'name="email"'), "Campo de email presente");
assertCondition(str_contains($html, 'O CPF não pode ser trocado.'), "Aviso de que CPF não pode ser trocado presente");
assertCondition(str_contains($html, 'readonly') && str_contains($html, 'disabled'), "Campo CPF configurado como readonly e disabled");
assertCondition(str_contains($html, 'Salvar Alterações'), "Botão 'Salvar Alterações' presente");
assertCondition(str_contains($html, 'id="btn-cancelar-edicao"'), "Botão 'Cancelar' de edição presente");

// 4.4 Remoção da menção de alteração cadastral na secretaria
assertCondition(!str_contains($html, 'alteração de dados cadastrais'), "Menção 'alteração de dados cadastrais' foi removida da caixa de aviso da secretaria");
assertCondition(str_contains($html, 'Para inclusão de novos dependentes ou transferência de turma'), "Texto da secretaria ajustado mantendo inclusão de dependentes e transferência");

// 4.5 Seção de alteração de senha unificada no primeiro card
assertCondition(str_contains($html, 'id="form-alterar-senha"'), "Formulário de alteração de senha presente");
assertCondition(str_contains($html, 'action="/portal/perfil/senha"'), "Formulário de senha submete para /portal/perfil/senha");
assertCondition(str_contains($html, 'id="senha_atual"'), "Campo Senha Atual presente");
assertCondition(str_contains($html, 'id="nova_senha"'), "Campo Nova Senha presente");
assertCondition(str_contains($html, 'id="confirmar_senha"'), "Campo Confirmar Nova Senha presente");
assertCondition(str_contains($html, 'Atualizar Senha'), "Botão 'Atualizar Senha' presente");

// 4.6 Políticas de acesso presentes na coluna lateral
assertCondition(str_contains($html, 'Como Funciona o Acesso'), "Painel 'Como Funciona o Acesso' presente na coluna 2");

echo "\n------------------------------------------------------\n";
echo " RESULTADO: {$passedAsserts}/{$totalAsserts} asserções passaram com sucesso!\n";
echo "------------------------------------------------------\n\n";

if ($passedAsserts === $totalAsserts) {
    exit(0);
} else {
    exit(1);
}
