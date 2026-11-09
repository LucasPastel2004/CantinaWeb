<?php
/**
 * Teste de Renderização: Perfil e Dados Cadastrais com Cabeçalhos Azuis e Textos Informativos
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoload do MVC
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=================================================\n";
echo "TESTE DE RENDERIZACAO DO PERFIL (DADOS CADASTRAIS)\n";
echo "=================================================\n\n";

$baseUrl = '';
$responsavel = [
    'id' => 813,
    'nome' => 'ADRIANE DA SILVA MARTINS',
    'cpf' => '99751216168',
    'whatsapp' => '(31) 98765-4321',
    'email' => 'lucashcc2306@gmail.com',
    'termos_aceitos_em' => '2026-11-06 08:57:00'
];
$dependentes = [
    [
        'id' => 101,
        'nome' => 'JÚLIA MARTINS GARCIA',
        'matricula' => '00002092',
        'nome_turma' => '5º Ano A',
        'turno' => 'matutino'
    ]
];
$csrfToken = 'test_token_123';

// 1. Renderiza a view perfil.php diretamente
ob_start();
include __DIR__ . '/../app/views/portal/perfil.php';
$html = ob_get_clean();

$testes = [
    'HTML gerado com sucesso' => strlen($html) > 1000,
    'Contém título Meus Dados Cadastrais' => str_contains($html, 'Meus Dados Cadastrais'),
    'Contém 4 cabeçalhos de bloco corporativos neutros' => substr_count($html, 'card-enterprise-header') === 4 && !str_contains($html, 'header-primary'),
    'Cabeçalho 1: Titular da Conta Familiar estilizado' => str_contains($html, 'Titular da Conta Familiar') && str_contains($html, 'status-success'),
    'Cabeçalho 2: Dependentes Vinculados estilizado' => str_contains($html, 'Dependentes Vinculados') && str_contains($html, '1 aluno(s)'),
    'Cabeçalho 3: Segurança de Acesso estilizado' => str_contains($html, 'Segurança de Acesso') && str_contains($html, 'Alteração de Senha'),
    'Cabeçalho 4: Como Funciona o Acesso estilizado' => str_contains($html, 'Como Funciona o Acesso') && str_contains($html, 'Protegido'),
    'Texto informativo de Atualização Cadastral restaurado' => str_contains($html, 'Para inclusão de novos dependentes, transferência de turma ou alteração de dados cadastrais, favor procurar a secretaria do Colégio Monteiro Lobato.'),
    'Texto informativo de Esqueceu sua senha? restaurado' => str_contains($html, 'Caso esqueça sua senha de acesso, na tela inicial de login clique em') && str_contains($html, 'As orientações seguras de redefinição serão despachadas para o seu e-mail cadastrado.'),
    'Texto explicativo de Autenticação e Senha presente' => str_contains($html, 'O acesso ao Portal da Família é individual por responsável legal, autenticado pelo CPF e senha criptografada em padrão seguro.')
];

$todosOk = true;
foreach ($testes as $desc => $passou) {
    if ($passou) {
        echo "  [PASS] {$desc}\n";
    } else {
        echo "  [FAIL] {$desc}\n";
        $todosOk = false;
    }
}

// 2. Verifica se custom.css contém a declaração de estilo dos cabeçalhos primários
$cssContent = file_get_contents(__DIR__ . '/../assets/css/custom.css');
$cssPassou = str_contains($cssContent, '.card-enterprise-header.header-primary') 
    && str_contains($cssContent, '--primary-blue')
    && str_contains($cssContent, 'color: #ffffff');

if ($cssPassou) {
    echo "  [PASS] custom.css define .card-enterprise-header.header-primary com --primary-blue\n";
} else {
    echo "  [FAIL] custom.css NÃO define .card-enterprise-header.header-primary corretamente\n";
    $todosOk = false;
}

// 3. Testa renderização com dependentes vazios
$dependentes = [];
ob_start();
include __DIR__ . '/../app/views/portal/perfil.php';
$htmlVazio = ob_get_clean();

$casoVazioPassou = str_contains($htmlVazio, 'Nenhum dependente vinculado no momento.')
    && str_contains($htmlVazio, '0 aluno(s)')
    && (substr_count($htmlVazio, 'card-enterprise-header') === 4);

if ($casoVazioPassou) {
    echo "  [PASS] Cenário de borda: Sem dependentes vinculados renderiza 0 aluno(s) e mensagem adequada\n";
} else {
    echo "  [FAIL] Cenário de borda com dependentes vazios falhou\n";
    $todosOk = false;
}

if ($todosOk) {
    echo "\n>>> TODOS OS TESTES DE PERFIL PASSARAM COM SUCESSO! <<<\n";
    exit(0);
} else {
    echo "\n[-] ALGUNS TESTES FALHARAM!\n";
    exit(1);
}
