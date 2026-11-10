<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Portal da Família (Pais e Responsáveis)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$title = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Portal da Família' 
    : 'Portal da Família — Colégio Monteiro Lobato';
$activeMenu = $activeMenu ?? 'inicio';

// Identifica usuário logado de forma resiliente
$loggedUser = $usuario ?? $user ?? \App\Core\Auth::user() ?? [];

// Garante o saldo correto: prioriza $saldoAtual passado explicitamente,
// ou busca de $loggedUser['saldo'], ou em fallback busca direto da sessão
if (!isset($saldoAtual) || $saldoAtual === null) {
    $saldoAtual = (float)($loggedUser['saldo'] ?? \App\Core\Session::get('_auth_saldo', 0.00));
} else {
    $saldoAtual = (float)$saldoAtual;
}

$saldoReservado = $saldoReservado ?? 0.00;

// O topo do portal (avatar, nome e menu do usuário) representa ESTRITAMENTE
// quem está logado na sessão ativa (ex: Danilla ou Aelson), nunca o titular financeiro.
$nomeUsuarioTopo = !empty($loggedUser['nome']) 
    ? $loggedUser['nome'] 
    : (\App\Core\Session::get('_auth_nome') ?? (!empty($responsavelNome) ? $responsavelNome : 'Responsável'));

// Formatação elegante do nome para evitar texto todo em maiúsculas
$preposicoesLayout = ['de', 'da', 'do', 'das', 'dos', 'e'];
$palavrasLayout = explode(' ', mb_strtolower(trim($nomeUsuarioTopo), 'UTF-8'));
$nomeFormatadoTopo = implode(' ', array_map(function($p) use ($preposicoesLayout) {
    return in_array($p, $preposicoesLayout, true) ? $p : mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
}, $palavrasLayout));
$inicialTopo = mb_strtoupper(mb_substr($nomeFormatadoTopo !== '' ? $nomeFormatadoTopo : 'R', 0, 1, 'UTF-8'), 'UTF-8');

$isSaldoBaixo = ($saldoAtual < 5.00);
$assetVersion = '7.4.4';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $title; ?></title>

    <!-- Bootstrap 5 Compilado Localmente -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">

    <!-- Design System Corporativo Anti-Slop -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=<?php echo $assetVersion; ?>">

    <!-- Favicon Oficial -->
    <link rel="icon" type="image/jpeg" href="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">

<!-- Topbar do Portal da Família -->
<header class="portal-header">
    <div class="container-fluid px-3 px-md-4 d-flex align-items-center justify-content-between" style="max-width: 1140px;">
        <!-- Identidade Oficial -->
        <a href="<?php echo $baseUrl; ?>/portal" class="d-flex align-items-center text-decoration-none">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
            <div class="d-flex flex-column">
                <span class="portal-brand-title">Monteiro Lobato</span>
                <span class="text-microcopy">Portal da Família</span>
            </div>
        </a>

        <!-- Widget de Saldo da Carteira Familiar (Azul Marinho Corporativo) -->
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-secondary btn-portal-header btn-sm text-decoration-none" title="Ver Carteira Familiar e Extrato">
                <span class="portal-header-label" style="font-size: 12px;">Saldo:</span>
                <strong class="font-tabular ms-1 portal-header-value" style="font-size: 13px;">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></strong>
            </a>

            <!-- Perfil do Responsável e Logout -->
            <div class="dropdown">
                <button class="btn btn-enterprise btn-secondary btn-portal-header btn-sm dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="portal-header-avatar d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 20px; height: 20px; font-size: 11px; font-weight: 700;">
                        <?php echo $inicialTopo; ?>
                    </span>
                    <span class="portal-header-username d-none d-md-inline"><?php echo htmlspecialchars($nomeFormatadoTopo, ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px; min-width: 220px;">
                    <li class="px-3 py-2 border-bottom bg-light">
                        <div class="font-weight-bold text-dark text-truncate" style="font-size: 13px;"><?php echo htmlspecialchars($nomeFormatadoTopo, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php
                        $isCoRespLayout = !empty($loggedUser['titular_id']) || (($loggedUser['parentesco'] ?? '') === 'co_responsavel');
                        $subtituloCargoLayout = $isCoRespLayout ? 'Co-responsável (Conta Familiar)' : 'Responsável Financeiro';
                        ?>
                        <div class="text-microcopy text-muted font-tabular"><?php echo $subtituloCargoLayout; ?></div>
                    </li>
                    <li><a class="dropdown-item mt-1" href="<?php echo $baseUrl; ?>/portal/perfil">Meus Dados</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="<?php echo $baseUrl; ?>/logout" method="POST" class="m-0">
                            <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                            <button type="submit" class="dropdown-item text-danger">Sair da Conta</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Barra de Navegação Primária do Portal -->
<nav class="portal-nav-bar d-none d-md-block">
    <div class="container-fluid px-3 px-md-4" style="max-width: 1140px;">
        <ul class="portal-nav-list">
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal" class="portal-nav-link <?php echo ($activeMenu === 'inicio') ? 'active' : ''; ?>">
                    Início
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/carteira" class="portal-nav-link <?php echo ($activeMenu === 'carteira') ? 'active' : ''; ?>">
                    Carteira Familiar
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/extrato" class="portal-nav-link <?php echo ($activeMenu === 'extrato') ? 'active' : ''; ?>">
                    Extrato do Aluno
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="portal-nav-link <?php echo ($activeMenu === 'agendamento') ? 'active' : ''; ?>">
                    Agendamento Semanal
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="portal-nav-link <?php echo ($activeMenu === 'pedidos') ? 'active' : ''; ?>">
                    Meus Pedidos
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/alergias" class="portal-nav-link <?php echo ($activeMenu === 'alergias') ? 'active' : ''; ?>">
                    Alergias & Restrições
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Conteúdo Principal do Portal -->
<main class="container-fluid px-3 px-md-4 my-4 flex-grow-1" style="max-width: 1140px;">
    <!-- Alertas do Sistema / Feedback de Ações -->
    <?php require __DIR__ . '/../partials/alert.php'; ?>

    <!-- Conteúdo da View Específica -->
    <?php echo $content ?? ''; ?>
</main>

<!-- Rodapé Institucional do Portal -->
<footer class="portal-footer mt-auto py-3">
    <div class="container-fluid px-3 px-md-4 d-flex flex-column flex-md-row align-items-center justify-content-between text-muted font-tabular" style="max-width: 1140px; font-size: 12px;">
        <div class="d-flex align-items-center gap-2 mb-1 mb-md-0">
            <span class="fw-semibold">Colégio Monteiro Lobato</span>
            <span>&bull;</span>
            <span class="fst-italic">Valores para o Futuro!</span>
        </div>
        <div>
            <span>Cantina Web &bull; Portal da Família &copy; <?php echo date('Y'); ?></span>
        </div>
    </div>
</footer>

<!-- Barra de Navegacao Inferior Mobile (6 Abas: 3 a Esquerda e 3 a Direita para Telas < 768px) -->
<nav class="mobile-bottom-nav d-md-none" aria-label="Navegação Rápida Mobile">
    <!-- Grupo Esquerda (3 Abas) -->
    <a href="<?php echo $baseUrl; ?>/portal" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'inicio') ? 'active' : ''; ?>" title="Início">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146z"/>
        </svg>
        <span>Início</span>
    </a>
    <a href="<?php echo $baseUrl; ?>/portal/carteira" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'carteira') ? 'active' : ''; ?>" title="Carteira">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
            <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
        </svg>
        <span>Carteira</span>
    </a>
    <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'agendamento') ? 'active' : ''; ?>" title="Agendar">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
        </svg>
        <span>Agendar</span>
    </a>

    <!-- Grupo Direita (3 Abas) -->
    <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'pedidos') ? 'active' : ''; ?>" title="Pedidos">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
            <path d="M5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 5 8zm0-2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-1-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zM4 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
        </svg>
        <span>Pedidos</span>
    </a>
    <a href="<?php echo $baseUrl; ?>/portal/extrato" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'extrato') ? 'active' : ''; ?>" title="Extrato">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27z"/>
        </svg>
        <span>Extrato</span>
    </a>
    <a href="<?php echo $baseUrl; ?>/portal/alergias" class="mobile-bottom-nav-item <?php echo ($activeMenu === 'alergias') ? 'active' : ''; ?>" title="Alergias">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
            <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.464 9.99a11.8 11.8 0 0 0 3.963 3.037.5.5 0 0 0 .464 0c1.248-.68 2.614-1.745 3.963-3.037 1.677-2.195 3.06-5.513 2.464-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0zm0 1.07c.613 0 1.698.257 2.753.543a60 60 0 0 1 2.668.803.54.54 0 0 1 .368.441c.548 4.108-.718 7.155-2.26 9.176a10.8 10.8 0 0 1-3.529 2.705 10.8 10.8 0 0 1-3.529-2.705C2.946 9.972 1.68 6.925 2.228 2.817a.54.54 0 0 1 .368-.442 60 60 0 0 1 2.668-.802C6.302 1.326 7.387 1.07 8 1.07z"/>
        </svg>
        <span>Alergias</span>
    </a>
</nav>

<!-- Bootstrap 5 Bundle Local -->
<script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- Scripts Vanilla JS Locais -->
<script src="<?php echo $baseUrl; ?>/assets/js/app.js?v=<?php echo $assetVersion ?? '7.4.2'; ?>"></script>
</body>
</html>
