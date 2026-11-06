<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Primeiro Acesso - Etapa 2: Inserção do E-mail Pessoal
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$emailSugerido = $emailSugerido ?? '';
$nomeResponsavel = $nomeResponsavel ?? '';
$primeiroNome = !empty($nomeResponsavel) ? explode(' ', trim($nomeResponsavel))[0] : 'Responsável';
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Informe seu E-mail</h2>
    <div class="text-microcopy text-secondary">
        Olá, <?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?>! Digite seu e-mail pessoal para receber o código de ativação.
    </div>
</div>

<form action="<?php echo $baseUrl; ?>/primeiro-acesso/enviar-codigo" method="POST" autocomplete="off" class="auth-form" id="form-primeiro-acesso-email">
    <?php echo $csrf_field ?? ''; ?>

    <div class="mb-3">
        <label for="email" class="form-label-enterprise">E-mail Pessoal</label>
        <input type="email" 
               class="form-control form-control-enterprise" 
               id="email" 
               name="email" 
               value="<?php echo htmlspecialchars($emailSugerido, ENT_QUOTES, 'UTF-8'); ?>" 
               placeholder="ex: seu.email@gmail.com"
               required 
               autofocus>
        <div class="text-microcopy text-muted mt-1">
            Enviaremos um código numérico de 6 dígitos para o seu e-mail.
        </div>
    </div>

    <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
        Enviar Código por E-mail
    </button>

    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
        <a href="<?php echo $baseUrl; ?>/primeiro-acesso" class="text-microcopy text-secondary text-decoration-none">
            &larr; Informar outro CPF
        </a>
        <a href="<?php echo $baseUrl; ?>/login?tab=familia" class="text-microcopy text-secondary text-decoration-none">
            Ir para o Login
        </a>
    </div>
</form>
