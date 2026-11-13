<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Inicial — Demonstração do Núcleo Arquitetural MVC (Fase 2)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
$baseUrl = $baseUrl ?? '';
$shortCsrf = substr($csrfToken ?? '', 0, 16) . '...';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?> — Núcleo Arquitetural</title>
    <!-- Bootstrap Compilado Localmente -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <!-- Design System Institucional e Anti-Slop Guidelines -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=<?php echo $assetVersion ?? '7.6.1'; ?>">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">
    <!-- Barra Superior Institucional Compacta (56px) -->
    <header class="app-header">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3" style="max-width: 1080px;">
            <div class="d-flex align-items-center">
                <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
                <div class="d-flex flex-column">
                    <span class="fw-bold" style="font-size: 13px; color: var(--text-main); letter-spacing: -0.01em;">Colégio Monteiro Lobato</span>
                    <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="status-indicator status-success">
                    <span class="bullet" aria-hidden="true">●</span> Sistema Operacional
                </span>
            </div>
        </div>
    </header>

    <main class="container my-4" style="max-width: 1080px;">
        <!-- Faixa de Métricas Executivas de Alta Densidade (Stat Strip) -->
        <section class="stat-strip" aria-label="Métricas de Infraestrutura">
            <div class="stat-strip-item">
                <span class="stat-strip-label">Ambiente Ativo</span>
                <span class="stat-strip-value text-capitalize"><?php echo htmlspecialchars($environment, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Interpretador</span>
                <span class="stat-strip-value font-tabular">PHP <?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Sessão / CSRF</span>
                <span class="stat-strip-value font-tabular text-success" title="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    Ativa (<?php echo htmlspecialchars($shortCsrf, ENT_QUOTES, 'UTF-8'); ?>)
                </span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Fase Vigente</span>
                <span class="stat-strip-value text-primary font-tabular"><?php echo htmlspecialchars($faseAtual ?? 'Fase 6 Concluída', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </section>

        <!-- Card Corporativo: Componentes Estruturais da Arquitetura -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Componentes Fundamentais do Núcleo MVC</span>
                    <span class="text-microcopy">&bull; Homologado</span>
                </div>
                <span class="text-microcopy font-tabular"><?php echo htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <!-- Grade de Dados Operacionais (Data Grid) -->
            <div class="table-responsive mb-2">
                <table class="table-data-grid">
                    <thead>
                        <tr>
                            <th class="cell-left" style="width: 20%;">Módulo</th>
                            <th class="cell-left" style="width: 25%;">Classe Implementada</th>
                            <th class="cell-center" style="width: 12%;">Status</th>
                            <th class="cell-left" style="width: 43%;">Responsabilidade e Regras de Negócio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Roteador</strong></td>
                            <td><code>App\Core\Router</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Roteamento HTTP nativo com parâmetros nomeados <code>{param}</code>, subpastas e 404 seguro.</td>
                        </tr>
                        <tr>
                            <td><strong>Controlador Base</strong></td>
                            <td><code>App\Core\Controller</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Métodos <code>render()</code> com XSS escape, <code>json()</code>, <code>redirect()</code> e validações com CSRF.</td>
                        </tr>
                        <tr>
                            <td><strong>Modelo Base</strong></td>
                            <td><code>App\Core\Model</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Prepared Statements nativos via PDO, transações atômicas e soft delete com <code>deleted_at</code>.</td>
                        </tr>
                        <tr>
                            <td><strong>Sessão Segura</strong></td>
                            <td><code>App\Core\Session</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Cookies HttpOnly/SameSite, controle de inatividade (60 min), antifixação e tokens CSRF.</td>
                        </tr>
                        <tr>
                            <td><strong>Tratamento de Erros</strong></td>
                            <td><code>App\Core\ErrorHandler</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Captura de exceções, tela segura com UUID e contingência com rotação de 5 MB em <code>error.log</code>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção Bento Grid: Acessos Operacionais e Diagnóstico -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div>
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Acessos Rápidos do Sistema</span>
                    <span class="text-microcopy d-block">Portais autenticados e endpoint oficial de status da aplicação</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">Portal da Família</span>
                                <code class="code-tag">WEB</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Acesso de Responsáveis</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Portal autenticado para recarga Pix, consulta de saldo e agendamento de lanches.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/portal" class="btn-enterprise btn-primary btn-sm w-100">
                            Abrir Portal da Família ↗
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">Painel Interno</span>
                                <code class="code-tag">ADMIN</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Painel Operacional da Cantina</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Área operacional de atendimento, PDV, estoque, caixa e relatórios gerenciais.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/admin" class="btn-enterprise btn-secondary btn-sm w-100">
                            Abrir Painel da Cantina ↗
                        </a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">API REST</span>
                                <code class="code-tag">JSON</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Endpoint de Status</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Retorno serializado via <code>$this->json()</code> com cabeçalho <code>application/json</code>.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/api/status" target="_blank" class="btn-enterprise btn-secondary btn-sm w-100">
                            Abrir Endpoint (/api/status) ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção das Fases 7, 8 & 9: Cadastros, Importação e Carteira Digital Pix -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Fases 7, 8 & 9 — Cadastros Mestres, Importação Escolar e Carteira Digital Pix</span>
                    <span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Concluídas</span>
                </div>
                <span class="text-microcopy font-tabular">Módulos 1, 2, 6 e 9</span>
            </div>
            <div class="p-3">
                <p class="text-secondary-label mb-3" style="font-size: 12px;">
                    Acesse diretamente os módulos funcionais implementados no Portal da Família e no Painel Administrativo.
                </p>
                <div class="row g-2">
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-primary btn-sm w-100 text-truncate" title="Carteira Familiar & Pix">
                            Carteira Familiar &amp; Pix
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <a href="<?php echo $baseUrl; ?>/portal/alergias" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Alergias & Restrições">
                            Alergias &amp; Restrições
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <a href="<?php echo $baseUrl; ?>/admin/importacao-escolar" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Importação Escolar">
                            Importação Escolar
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <a href="<?php echo $baseUrl; ?>/admin/alunos" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Alunos & Matrículas">
                            Alunos &amp; Matrículas
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/admin/configuracoes" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Configurações Globais">
                            Configurações
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/admin/produtos" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Cardápio & Preços">
                            Cardápio &amp; Preços
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/admin/categorias" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Categorias">
                            Categorias
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/admin/colaboradores" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Colaboradores">
                            Colaboradores
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/admin/turmas" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Turmas">
                            Turmas
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4 col-lg-2">
                        <a href="<?php echo $baseUrl; ?>/login" class="btn btn-enterprise btn-secondary btn-sm w-100 text-truncate" title="Login">
                            Acesso / Login
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próxima Fase do Projeto -->
        <div class="p-3 bg-white rounded border d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <span class="status-indicator status-neutral font-tabular" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Próxima Etapa</span>
                <span class="text-secondary-label" style="font-size: 12px;">
                    <strong>Fase 10:</strong> Agendamento Semanal de Lanches e Regras Rígidas de Corte (08:00 e 13:30).
                </span>
            </div>
            <a href="https://github.com/LucasPastel2004/cantina" target="_blank" class="btn-enterprise btn-secondary btn-sm">
                Repositório GitHub ↗
            </a>
        </div>
    </main>

    <!-- Rodapé Corporativo Sóbrio -->
    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        Colégio Monteiro Lobato &copy; <?php echo date('Y'); ?> — Todos os direitos reservados.
    </footer>

    <!-- Bootstrap Bundle Local (Vanilla JS) -->
    <script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/app.js?v=<?php echo $assetVersion ?? '7.6.1'; ?>"></script>
</body>
</html>
