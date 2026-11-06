<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Integração: Novo Fluxo de Primeiro Acesso e Notificações por E-mail
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/services/EmailService.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Services\EmailService;
use App\Controllers\AuthController;

echo "======================================================================" . PHP_EOL;
echo "  TESTES: MENSAGERIA POR E-MAIL E NOVO FLUXO DE PRIMEIRO ACESSO" . PHP_EOL;
echo "======================================================================" . PHP_EOL;

Session::start();
$emailService = new EmailService();
$authController = new AuthController();
$csrfToken = Session::generateCsrfToken();

$passed = 0;
$failed = 0;

function assercao(bool $cond, string $msg, &$p, &$f) {
    if ($cond) {
        echo "  [PASS] {$msg}" . PHP_EOL;
        $p++;
    } else {
        echo "  [FAIL] {$msg}" . PHP_EOL;
        $f++;
    }
}

// 1. Testes Unitários do EmailService
echo PHP_EOL . "--- 1. Testes Unitários do EmailService ---" . PHP_EOL;

$codigo = EmailService::generateCode();
assercao(strlen($codigo) === 6 && ctype_digit($codigo), "generateCode() gerou 6 dígitos numéricos: {$codigo}", $passed, $failed);

$masked1 = EmailService::maskEmail('lucas.campos@gmail.com');
assercao($masked1 === 'l***s@gmail.com', "maskEmail('lucas.campos@gmail.com') -> '{$masked1}'", $passed, $failed);

$masked2 = EmailService::maskEmail('contato@cantinawebmonteirolobato.com.br');
assercao($masked2 === 'c***o@cantinawebmonteirolobato.com.br', "maskEmail('contato@...') -> '{$masked2}'", $passed, $failed);

$envio1 = $emailService->sendFirstAccessCode('pai.teste@escola.com.br', '123456', 'Carlos Eduardo');
assercao($envio1 === true, "sendFirstAccessCode() despachou e-mail de primeiro acesso com sucesso", $passed, $failed);
assercao(EmailService::getLastCode('pai.teste@escola.com.br') === '123456', "getLastCode() recuperou o código '123456' despachado", $passed, $failed);

$envio2 = $emailService->sendPasswordResetCode('mae.teste@escola.com.br', '654321', 'Ana Paula');
assercao($envio2 === true, "sendPasswordResetCode() despachou e-mail de recuperação com sucesso", $passed, $failed);

$envio3 = $emailService->sendRecargaConfirmada('pai.teste@escola.com.br', 50.00, 75.50, 'Carlos');
assercao($envio3 === true, "sendRecargaConfirmada() despachou comprovante Pix com sucesso", $passed, $failed);

$envio4 = $emailService->sendAlertaSaldoBaixo('pai.teste@escola.com.br', 12.00, 'Carlos');
assercao($envio4 === true, "sendAlertaSaldoBaixo() despachou alerta preventivo com sucesso", $passed, $failed);

$envio5 = $emailService->sendNotificacaoMed('pai.teste@escola.com.br', 50.00, -25.00, 'Carlos');
assercao($envio5 === true, "sendNotificacaoMed() despachou notificação MED com sucesso", $passed, $failed);

$envio6 = $emailService->sendNotificacaoDesbloqueioMed('pai.teste@escola.com.br', 30.00, 'Carlos');
assercao($envio6 === true, "sendNotificacaoDesbloqueioMed() despachou liberação MED com sucesso", $passed, $failed);

// 2. Teste do Novo Fluxo de Primeiro Acesso por E-mail
echo PHP_EOL . "--- 2. Simulação do Novo Fluxo de Primeiro Acesso (CPF -> E-mail -> Código -> Senha) ---" . PHP_EOL;

$cpfTeste = '55566677788';
$emailTeste = 'responsavel.novo@teste.com';
$db = \Database::getInstance();

// Reseta o responsável para pendente com e-mail sintético (simulando a base real da escola)
$db->query("UPDATE responsaveis SET email = '{$cpfTeste}@colegiomonteirolobato.com.br', senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '{$cpfTeste}'");

// Etapa 1: Inserção do CPF
$_POST = [
    'csrf_token' => $csrfToken,
    'cpf'        => '555.666.777-88'
];
Session::remove('_pa_responsavel_id');
Session::remove('_pa_email');
Session::remove('_pa_codigo');

ob_start();
$authController->primeiroAcessoSolicitar();
ob_end_clean();

assercao(Session::has('_pa_responsavel_id'), "Etapa 1 aprovada: salvou responsavel_id na sessão", $passed, $failed);
assercao(Session::get('_pa_cpf') === $cpfTeste, "Etapa 1 aprovada: CPF higienizado na sessão", $passed, $failed);

// Renderização da view de E-mail (Etapa 2)
ob_start();
$authController->primeiroAcessoEmail();
$htmlEmail = ob_get_clean();
assercao(str_contains($htmlEmail, 'Informe seu E-mail'), "Etapa 2 exibe tela de 'Informe seu E-mail'", $passed, $failed);
assercao(!str_contains($htmlEmail, '@colegiomonteirolobato.com.br'), "Etapa 2 BLINDADA: e-mail sintético da escola NÃO é exibido no campo", $passed, $failed);
assercao(str_contains($htmlEmail, 'value=""'), "Etapa 2 inicia com o campo de e-mail completamente vazio para o pai digitar", $passed, $failed);
assercao(str_contains($htmlEmail, 'Enviar Código por E-mail'), "Etapa 2 exibe botão 'Enviar Código por E-mail'", $passed, $failed);

// Submissão do novo E-mail
$_POST = [
    'csrf_token' => $csrfToken,
    'email'      => $emailTeste
];

ob_start();
$authController->primeiroAcessoEnviarCodigo();
ob_end_clean();

$codigoGerado = Session::get('_pa_codigo');
assercao(!empty($codigoGerado) && strlen($codigoGerado) === 6, "Etapa 2 gerou código de 6 dígitos: {$codigoGerado}", $passed, $failed);
assercao(Session::get('_pa_email') === $emailTeste, "Etapa 2 registrou o e-mail na sessão: {$emailTeste}", $passed, $failed);

// Verifica se o e-mail foi atualizado no banco
$respDb = Responsavel::fetchOne("SELECT email FROM responsaveis WHERE cpf = '{$cpfTeste}' LIMIT 1");
assercao(($respDb['email'] ?? '') === $emailTeste, "Banco de dados atualizado com o novo e-mail informado pelo pai", $passed, $failed);

// Etapa 3: Validação do Código recebido no e-mail
$_POST = [
    'csrf_token' => $csrfToken,
    'codigo'     => $codigoGerado
];

ob_start();
$authController->primeiroAcessoValidarCodigo();
ob_end_clean();

assercao(Session::get('_pa_verificado') === true, "Etapa 3: Código de 6 dígitos validado com sucesso", $passed, $failed);

// Etapa 4: Conclusão do cadastro de senha e aceite dos termos
$_POST = [
    'csrf_token'        => $csrfToken,
    'senha'             => 'NovaSenha@2026',
    'senha_confirmacao' => 'NovaSenha@2026',
    'aceite_termos'     => '1'
];

ob_start();
$authController->primeiroAcessoConcluir();
ob_end_clean();

$respAtivado = Responsavel::fetchOne("SELECT senha_hash, termos_aceitos_em FROM responsaveis WHERE cpf = '{$cpfTeste}' LIMIT 1");
assercao(!empty($respAtivado['senha_hash']), "Etapa 4: Senha criptografada gravada no banco", $passed, $failed);
assercao(!empty($respAtivado['termos_aceitos_em']), "Etapa 4: Termos aceitos gravados no banco", $passed, $failed);

// Teardown: Restaura o responsável de teste para pendente
$db->query("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '{$cpfTeste}'");
Session::destroy();

echo PHP_EOL . "======================================================================" . PHP_EOL;
echo "  RESUMO DOS TESTES DE E-MAIL E PRIMEIRO ACESSO" . PHP_EOL;
echo "  Total: " . ($passed + $failed) . " | Sucessos: {$passed} | Falhas: {$failed}" . PHP_EOL;
echo "======================================================================" . PHP_EOL;

if ($failed === 0) {
    echo "TODAS AS ASSERÇÕES PASSARAM COM 100% DE SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo "FALHA EM UMA OU MAIS ASSERÇÕES!" . PHP_EOL;
    exit(1);
}
