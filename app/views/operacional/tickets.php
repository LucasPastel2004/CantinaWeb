<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$buscaTermo = $buscaTermo ?? '';
$metricas = $metricas ?? [
    'total_tickets'       => 0,
    'total_impressos'     => 0,
    'total_pendentes'     => 0,
    'total_com_alergenos' => 0,
    'total_alunos'        => 0,
    'total_turmas'        => 0
];
$turmasAgrupadas = $turmasAgrupadas ?? [];
$ticketsConsulta = $ticketsConsulta ?? [];
$csrf_token = $csrf_token ?? '';
$jaFiltrado = $jaFiltrado ?? false;
$turmaId = $turmaId ?? null;
$statusTicket = $statusTicket ?? '';
$turmasLista = $turmasLista ?? [];

$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turnoNome = ($turnoConsumo === 'matutino') ? 'Matutino' : 'Vespertino';
?>

<!-- Alertas Flash do Sistema -->
<?php require __DIR__ . '/../partials/alert.php'; ?>

<!-- Topo da Página com Título e Contexto -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Gestão e Impressão de Tickets Térmicos (80mm)</h1>
            <span class="badge bg-light text-dark border" style="font-size: 11px; font-weight: 600;">
                Turno <?php echo htmlspecialchars($turnoNome, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Emissão contínua de fichas de consumo para distribuição prévia em sala de aula e consulta rápida de 2ª via.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Folha A4 (Grade de Fichas para Impressora Comum e Exportação em PDF) -->
        <a href="<?php echo $baseUrl; ?>/operacional/tickets/lote?formato=a4&data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           target="_blank"
           class="btn btn-enterprise btn-primary btn-sm"
           title="Visualizar, imprimir ou exportar fichas em Folha A4 (grade de 8 tickets por folha)">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir / Salvar PDF (A4)
        </a>

        <!-- Bobina Térmica 80mm -->
        <a href="<?php echo $baseUrl; ?>/operacional/tickets/lote?formato=80mm&data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           target="_blank"
           class="btn btn-enterprise btn-secondary btn-sm"
           title="Abrir bobina contínua 80mm para impressora térmica de cupom ou PDF">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Bobina Térmica (80mm)
        </a>
    </div>
</div>

<!-- Barra de Filtros Reativos (Data e Turno) -->
<div class="card card-enterprise mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/operacional/tickets" class="row g-2 align-items-center" id="formFiltroTickets">
            <div class="col-auto">
                <label for="filtroData" class="form-label text-microcopy mb-0 fw-semibold">Data de Consumo:</label>
            </div>
            <div class="col-auto">
                <input type="date" 
                       id="filtroData" 
                       name="data" 
                       value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>" 
                       class="form-control form-control-sm font-tabular"
                       onchange="this.form.submit()"
                       style="width: 150px;">
            </div>

            <div class="col-auto ms-lg-3">
                <label for="filtroTurno" class="form-label text-microcopy mb-0 fw-semibold">Turno:</label>
            </div>
            <div class="col-auto">
                <select id="filtroTurno" 
                        name="turno" 
                        class="form-select form-select-sm" 
                        onchange="this.form.submit()"
                        style="width: 140px;">
                    <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>

            <div class="col-auto ms-auto text-secondary text-microcopy">
                <span>Ordenação de Impressão: <strong>Ordem da Turma &gt; Turma &gt; Aluno &gt; Ticket</strong></span>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip com Métricas do Turno -->
<div class="stat-strip mb-4">
    <div class="stat-strip-item">
        <span class="stat-strip-label">Total de Tickets</span>
        <span class="stat-strip-value font-tabular"><?php echo (int)$metricas['total_tickets']; ?></span>
        <span class="stat-strip-sub">Fichas para <?php echo htmlspecialchars($dataFmt, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Tickets Impressos</span>
        <span class="stat-strip-value font-tabular text-success"><?php echo (int)$metricas['total_impressos']; ?></span>
        <span class="stat-strip-sub">Já gerados na bobina</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Tickets Pendentes</span>
        <span class="stat-strip-value font-tabular text-dark"><?php echo (int)$metricas['total_pendentes']; ?></span>
        <span class="stat-strip-sub">Aguardando impressão</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Alertas de Alérgenos</span>
        <span class="stat-strip-value font-tabular <?php echo ($metricas['total_com_alergenos'] > 0) ? 'text-warning' : 'text-secondary'; ?>">
            <?php echo (int)$metricas['total_com_alergenos']; ?>
        </span>
        <span class="stat-strip-sub">Com restrições (RN-16/17)</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Turmas Atendidas</span>
        <span class="stat-strip-value font-tabular text-dark"><?php echo (int)$metricas['total_turmas']; ?></span>
        <span class="stat-strip-sub">Blocos de entrega</span>
    </div>
</div>

<!-- Seção 1: Lotes por Turma Escolar (Distribuição em Sala de Aula) -->
<div class="card card-enterprise mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <div>
            <h2 class="h6 mb-0 font-weight-bold text-dark">Lotes de Fichas por Turma Escolar</h2>
            <span class="text-secondary text-microcopy">Separação ordenada por blocos de sala de aula para entrega prévia aos professores</span>
        </div>
        <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px;">
            <?php echo count($turmasAgrupadas); ?> turmas com pedidos
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">Ordem</th>
                    <th style="width: 200px;">Série / Turma</th>
                    <th style="width: 130px;" class="text-center">Alunos Beneficiados</th>
                    <th style="width: 120px;" class="text-center">Total de Tickets</th>
                    <th style="width: 150px;">Alérgenos</th>
                    <th style="width: 140px;" class="text-center">Status Lote</th>
                    <th style="width: 160px;" class="text-end pe-3">Ações da Turma</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($turmasAgrupadas)): ?>
                    <tr>
                        <td colspan="7" class="empty-state py-4 text-center text-secondary">
                            <div class="text-microcopy">Não há nada para mostrar aqui</div>
                            <div class="text-secondary" style="font-size: 11px; margin-top: 4px;">
                                Nenhum pedido de lanche ativo para o turno e data selecionados.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($turmasAgrupadas as $tg): ?>
                        <tr>
                            <td class="text-center font-tabular fw-bold text-secondary">
                                #<?php echo (int)$tg['ordem_impressao']; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($tg['nome_turma'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy"><?php echo htmlspecialchars($tg['serie'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="text-center font-tabular">
                                <?php echo (int)$tg['total_alunos']; ?> alunos
                            </td>
                            <td class="text-center font-tabular fw-bold text-dark">
                                <?php echo (int)$tg['total_tickets']; ?> fichas
                            </td>
                            <td>
                                <?php if ($tg['total_com_alergenos'] > 0): ?>
                                    <span class="badge bg-warning text-dark font-tabular" style="font-size: 11px; font-weight: 600;">
                                        ⚠ <?php echo (int)$tg['total_com_alergenos']; ?> com restrição
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy">Sem restrições</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tg['total_pendentes'] === 0): ?>
                                    <span class="status-indicator status-active" style="font-size: 11px;">
                                        <span class="bullet">●</span> Impresso
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px;">
                                        <?php echo (int)$tg['total_pendentes']; ?> pendente(s)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?php echo $baseUrl; ?>/operacional/tickets/turma/<?php echo (int)$tg['turma_id']; ?>?formato=a4&data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                                       target="_blank"
                                       class="btn btn-enterprise btn-primary btn-sm py-0 px-2"
                                       style="font-size: 11px;"
                                       title="Imprimir ou salvar em PDF as fichas desta turma em Folha A4">
                                        Imprimir / PDF A4
                                    </a>
                                    <a href="<?php echo $baseUrl; ?>/operacional/tickets/turma/<?php echo (int)$tg['turma_id']; ?>?formato=80mm&data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                                       target="_blank"
                                       class="btn btn-enterprise btn-secondary btn-sm py-0 px-2"
                                       style="font-size: 11px;"
                                       title="Imprimir ou salvar em PDF a bobina contínua 80mm desta turma">
                                        80mm
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Seção 2: Consulta Rápida e Emissão de 2ª Via (Carga Sob Demanda) -->
<div class="card card-enterprise mb-4">
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0 font-weight-bold text-dark">Consulta Rápida de Tickets &amp; Emissão de 2ª Via</h2>
            <span class="text-secondary text-microcopy">Localização instantânea para atender alunos que perderam ou danificaram a ficha</span>
        </div>
        <?php if (!empty($jaFiltrado)): ?>
            <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px;">
                <?php echo count($ticketsConsulta); ?> ticket(s) localizado(s)
            </span>
        <?php endif; ?>
    </div>

    <!-- Barra de Filtros e Busca de Tickets (Padrão de Carga Sob Demanda) -->
    <div class="p-3 bg-light border-bottom">
        <form method="GET" action="<?php echo $baseUrl; ?>/operacional/tickets" class="row g-2 align-items-end">
            <input type="hidden" name="data" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="turno" value="<?php echo htmlspecialchars($turnoConsumo, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="filtrar" value="1">

            <div class="col-12 col-md-4 position-relative">
                <label for="filtroQ" class="form-label text-microcopy text-secondary mb-1 fw-semibold text-uppercase" style="font-size: 10px;">
                    Buscar por Aluno, Matrícula ou Código
                </label>
                <div class="position-relative">
                    <input type="text" 
                           id="filtroQ"
                           name="q" 
                           class="form-control form-control-sm" 
                           placeholder="Digite nome, matrícula, pedido ou código..." 
                           value="<?php echo htmlspecialchars($buscaTermo, ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="off"
                           aria-label="Buscar tickets"
                           aria-autocomplete="list"
                           aria-controls="buscaSugestoesTicketsDropdown">
                    <!-- Dropdown flutuante de sugestões em tempo real -->
                    <div id="buscaSugestoesTicketsDropdown" class="shadow-sm position-absolute rounded" 
                         style="top: calc(100% + 4px); left: 0; right: 0; z-index: 1060; max-height: 290px; overflow-y: auto; display: none; background: #ffffff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.18), 0 8px 10px -6px rgba(15, 23, 42, 0.1) !important;">
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label for="filtroTurma" class="form-label text-microcopy text-secondary mb-1 fw-semibold text-uppercase" style="font-size: 10px;">
                    Turma Escolar
                </label>
                <select id="filtroTurma" name="turma_id" class="form-select form-select-sm">
                    <option value="">Todas as Turmas</option>
                    <?php if (!empty($turmasLista)): ?>
                        <?php foreach ($turmasLista as $turma): ?>
                            <option value="<?php echo (int)$turma['id']; ?>" <?php echo ($turmaId === (int)$turma['id']) ? 'selected' : ''; ?>>
                                <?php 
                                $serieComplemento = !empty($turma['serie']) ? ' (' . $turma['serie'] . ')' : '';
                                echo htmlspecialchars($turma['nome_turma'] . $serieComplemento, ENT_QUOTES, 'UTF-8'); 
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="filtroStatusTicket" class="form-label text-microcopy text-secondary mb-1 fw-semibold text-uppercase" style="font-size: 10px;">
                    Status do Ticket
                </label>
                <select id="filtroStatusTicket" name="status_ticket" class="form-select form-select-sm">
                    <option value="">Todos os Status</option>
                    <option value="impresso" <?php echo ($statusTicket === 'impresso') ? 'selected' : ''; ?>>Apenas Impressos</option>
                    <option value="pendente" <?php echo ($statusTicket === 'pendente') ? 'selected' : ''; ?>>Apenas Pendentes</option>
                    <option value="segunda_via" <?php echo ($statusTicket === 'segunda_via') ? 'selected' : ''; ?>>Apenas 2ª Via Emitida</option>
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill">
                    Buscar
                </button>
                <?php if (!empty($jaFiltrado)): ?>
                    <a href="<?php echo $baseUrl; ?>/operacional/tickets?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                       class="btn btn-enterprise btn-secondary btn-sm" 
                       title="Limpar Filtro">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($jaFiltrado)): ?>
        <div class="p-3">
            <?php 
            $emptyMessage = 'Não há nada para mostrar aqui';
            $emptyDescription = 'Utilize os filtros acima ou clique em "Buscar" para listar os tickets deste turno.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        </div>
    <?php elseif (empty($ticketsConsulta)): ?>
        <div class="p-3">
            <?php 
            $emptyMessage = 'Nenhum ticket encontrado';
            $emptyDescription = 'Nenhum ticket localizado com os termos informados para este turno.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 160px;">Código do Ticket</th>
                        <th style="width: 220px;">Aluno / Matrícula</th>
                        <th style="width: 140px;">Turma</th>
                        <th style="width: 220px;">Produto / Salgado</th>
                        <th style="width: 140px;">Alérgenos</th>
                        <th style="width: 100px;" class="text-center">Via</th>
                        <th style="width: 110px;" class="text-center">Status</th>
                        <th style="width: 150px;" class="text-end pe-3">Ação 2ª Via</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ticketsConsulta as $tc): ?>
                        <tr>
                            <td>
                                <span class="code-tag font-tabular fw-bold"><?php echo htmlspecialchars($tc['codigo_ticket'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                <div class="text-secondary text-microcopy mt-1 font-tabular">Ped: <?php echo htmlspecialchars($tc['codigo_pedido'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($tc['aluno_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy font-tabular">Mat: <?php echo htmlspecialchars($tc['aluno_matricula'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="text-dark"><?php echo htmlspecialchars($tc['nome_turma'] ?? 'Sem Turma', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy"><?php echo htmlspecialchars($tc['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($tc['produto_descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy font-tabular">
                                    R$ <?php echo number_format((float)($tc['produto_preco_venda'] ?? 0), 2, ',', '.'); ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($tc['tag_alergenos'])): ?>
                                    <span class="badge bg-warning text-dark text-wrap" style="font-size: 10px; font-weight: 700; line-height: 1.2;">
                                        <?php echo htmlspecialchars($tc['tag_alergenos'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-tabular">
                                <?php $via = (int)($tc['numero_via'] ?? 1); ?>
                                <?php if ($via > 1): ?>
                                    <span class="badge bg-danger text-white" style="font-size: 10px; font-weight: 700;">
                                        <?php echo $via; ?>ª Via
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;">
                                        1ª Via
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                $st = $tc['ticket_status'] ?? 'ativo';
                                if ($st === 'impresso'): ?>
                                    <span class="status-indicator status-active" style="font-size: 11px;">
                                        <span class="bullet">●</span> Impresso
                                    </span>
                                <?php elseif ($st === 'ativo'): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;">
                                        Pendente
                                    </span>
                                <?php elseif ($st === 'estornado'): ?>
                                    <span class="badge bg-secondary text-white" style="font-size: 10px;">
                                        Estornado
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                                        <?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($st !== 'cancelado' && $st !== 'estornado'): ?>
                                    <button type="button" 
                                            class="btn btn-enterprise btn-secondary btn-sm"
                                            onclick="abrirModalSegundaVia(<?php echo (int)$tc['ticket_id']; ?>, '<?php echo htmlspecialchars(addslashes($tc['codigo_ticket']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($tc['aluno_nome']), ENT_QUOTES, 'UTF-8'); ?>', <?php echo (int)($tc['numero_via'] ?? 1); ?>)"
                                            title="Emitir 2ª via com justificativa compulsória">
                                        Reimprimir 2ª Via
                                    </button>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy italic">Indisponível</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Corporativo: Emissão de 2ª Via com Justificativa Compulsória (RN-16 e Auditoria) -->
<div class="modal fade" id="modalSegundaVia" tabindex="-1" aria-labelledby="modalSegundaViaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?php echo $baseUrl; ?>/operacional/tickets/segunda-via">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ticket_id" id="modalTicketId" value="">

                <div class="modal-header py-2 px-3 bg-light border-bottom">
                    <h5 class="modal-title h6 fw-bold text-dark mb-0" id="modalSegundaViaLabel">
                        Reemissão de 2ª Via de Ticket Térmico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 11px; line-height: 1.4;">
                        <strong>Aviso de Auditoria:</strong> A emissão de 2ª via substitui o cupom anterior, carimba a nova etiqueta impressa com o destaque <code>[2ª VIA]</code> e grava um <strong>registro permanente e obrigatório (indelével — que não pode ser apagado)</strong> no histórico de auditoria do sistema, identificando seu usuário e endereço IP. Use apenas em caso de perda ou dano físico comprovado.
                    </div>

                    <div class="p-2 mb-3 bg-light border rounded" style="font-size: 12px;">
                        <div><strong>Código do Ticket:</strong> <span class="font-tabular fw-bold text-primary" id="modalTicketCodigo">—</span></div>
                        <div><strong>Aluno:</strong> <span id="modalAlunoNome">—</span></div>
                        <div><strong>Via Atual:</strong> <span class="font-tabular" id="modalViaAtual">—</span> &rarr; <span class="text-danger fw-bold" id="modalNovaVia">—</span></div>
                    </div>

                    <div class="mb-2">
                        <label for="modalJustificativa" class="form-label fw-bold text-dark text-microcopy mb-1">
                            Justificativa Operacional Obrigatória: <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" 
                                  id="modalJustificativa" 
                                  name="justificativa" 
                                  rows="3" 
                                  required 
                                  minlength="5"
                                  placeholder="Ex: Aluno informou perda da ficha em sala de aula / Ticket rasgado..."></textarea>
                        <div class="text-secondary text-microcopy mt-1">Mínimo de 5 caracteres.</div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">
                        Confirmar Reemissão (2ª Via)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalSegundaVia(ticketId, codigoTicket, alunoNome, viaAtual) {
    document.getElementById('modalTicketId').value = ticketId;
    document.getElementById('modalTicketCodigo').textContent = codigoTicket;
    document.getElementById('modalAlunoNome').textContent = alunoNome;
    document.getElementById('modalViaAtual').textContent = viaAtual + 'ª Via';
    document.getElementById('modalNovaVia').textContent = (viaAtual + 1) + 'ª Via (Reimpressão)';
    document.getElementById('modalJustificativa').value = '';

    const modalEl = document.getElementById('modalSegundaVia');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        setTimeout(() => {
            document.getElementById('modalJustificativa').focus();
        }, 400);
    } else {
        // Fallback defensivo caso modal JS não esteja pronto
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
    }
}

// =========================================================================
// AUTOCOMPLETE DE SUGESTÕES EM TEMPO REAL PARA TICKETS & 2ª VIA
// =========================================================================
(function() {
    const inputBusca = document.getElementById('filtroQ');
    const dropdown = document.getElementById('buscaSugestoesTicketsDropdown');
    const formFiltros = inputBusca ? inputBusca.closest('form') : null;
    const baseUrl = '<?php echo $baseUrl; ?>';
    const dataConsumo = '<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>';
    const turnoConsumo = '<?php echo htmlspecialchars($turnoConsumo, ENT_QUOTES, 'UTF-8'); ?>';

    if (!inputBusca || !dropdown || !formFiltros) return;

    let debounceTimer = null;
    let itemAtivoIndex = -1;
    let sugestoesAtuais = [];

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fecharDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        itemAtivoIndex = -1;
        sugestoesAtuais = [];
    }

    function destacarItem(index) {
        const itens = dropdown.querySelectorAll('.sugestao-ticket-item');
        itens.forEach((el, i) => {
            if (i === index) {
                el.style.backgroundColor = '#f1f5f9';
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.style.backgroundColor = '#ffffff';
            }
        });
    }

    function selecionarSugestao(item) {
        if (!item) return;
        const valorDigitado = inputBusca.value.trim().toUpperCase();
        if (valorDigitado.startsWith('TCK')) {
            inputBusca.value = item.codigo_ticket || item.aluno_nome;
        } else if (valorDigitado.startsWith('PED')) {
            inputBusca.value = item.codigo_pedido || item.aluno_nome;
        } else {
            inputBusca.value = item.aluno_nome;
        }

        fecharDropdown();
        formFiltros.submit();
    }

    function buscarSugestoes(termo) {
        if (!termo || termo.length < 1) {
            fecharDropdown();
            return;
        }

        dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">' +
            '<span class="spinner-border spinner-border-sm me-1 text-primary" role="status"></span> Buscando tickets...</div>';
        dropdown.style.display = 'block';

        const url = baseUrl + '/operacional/tickets/sugestoes?q=' + encodeURIComponent(termo) +
            '&data=' + encodeURIComponent(dataConsumo) +
            '&turno=' + encodeURIComponent(turnoConsumo);

        fetch(url)
            .then(res => {
                if (!res.ok) throw new Error('Falha na requisição');
                return res.json();
            })
            .then(data => {
                // Descarta resposta de requisição assíncrona se o usuário já limpou ou alterou o texto
                if (inputBusca.value.trim() !== termo) {
                    if (!inputBusca.value.trim()) {
                        fecharDropdown();
                    }
                    return;
                }

                const lista = data.sugestoes || [];
                sugestoesAtuais = lista;
                itemAtivoIndex = -1;

                if (lista.length === 0) {
                    dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">' +
                        'Nenhum ticket encontrado para "' + escapeHtml(termo) + '".</div>';
                    dropdown.style.display = 'block';
                    return;
                }

                let html = '';
                lista.forEach((item, idx) => {
                    const viaBadge = (parseInt(item.numero_via, 10) > 1)
                        ? '<span class="badge bg-danger text-white ms-1" style="font-size: 9px; font-weight: 700;">' + item.numero_via + 'ª Via</span>'
                        : '<span class="badge bg-light text-secondary border ms-1" style="font-size: 9px;">1ª Via</span>';

                    html += '<div class="sugestao-ticket-item px-3 py-2 border-bottom text-start" data-idx="' + idx + '" role="option" tabindex="0" style="cursor: pointer; background: #ffffff;">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                            '<div>' +
                                '<strong class="text-dark" style="font-size: 13px;">' + escapeHtml(item.aluno_nome) + '</strong>' +
                                '<span class="badge bg-light text-dark border ms-2" style="font-size: 9.5px;">' + escapeHtml(item.nome_turma) + '</span>' +
                                viaBadge +
                            '</div>' +
                            '<code class="code-tag font-tabular" style="font-size: 10.5px;">' + escapeHtml(item.codigo_ticket) + '</code>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between text-secondary text-microcopy" style="font-size: 11px;">' +
                            '<span>' + escapeHtml(item.produto_descricao) + '</span>' +
                            '<span>Mat: <strong class="font-tabular">' + escapeHtml(item.aluno_matricula || '—') + '</strong> &bull; Ped: ' + escapeHtml(item.codigo_pedido) + '</span>' +
                        '</div>' +
                    '</div>';
                });

                dropdown.innerHTML = html;
                dropdown.style.display = 'block';

                dropdown.querySelectorAll('.sugestao-ticket-item').forEach(el => {
                    el.addEventListener('mouseenter', () => {
                        const idx = parseInt(el.getAttribute('data-idx'), 10);
                        itemAtivoIndex = idx;
                        destacarItem(idx);
                    });
                    el.addEventListener('click', e => {
                        e.preventDefault();
                        e.stopPropagation();
                        const idx = parseInt(el.getAttribute('data-idx'), 10);
                        selecionarSugestao(sugestoesAtuais[idx]);
                    });
                });
            })
            .catch(() => {
                dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">Erro ao carregar sugestões.</div>';
                dropdown.style.display = 'block';
            });
    }

    inputBusca.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const termo = inputBusca.value;
        if (!termo.trim()) {
            fecharDropdown();
            return;
        }
        debounceTimer = setTimeout(() => {
            buscarSugestoes(termo.trim());
        }, 180);
    });

    inputBusca.addEventListener('keydown', e => {
        const total = sugestoesAtuais.length;
        if (dropdown.style.display === 'block' && total > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                itemAtivoIndex = (itemAtivoIndex + 1) >= total ? 0 : itemAtivoIndex + 1;
                destacarItem(itemAtivoIndex);
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                itemAtivoIndex = (itemAtivoIndex - 1) < 0 ? total - 1 : itemAtivoIndex - 1;
                destacarItem(itemAtivoIndex);
                return;
            }
            if (e.key === 'Enter') {
                if (itemAtivoIndex >= 0 && itemAtivoIndex < total) {
                    e.preventDefault();
                    selecionarSugestao(sugestoesAtuais[itemAtivoIndex]);
                    return;
                }
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                fecharDropdown();
                return;
            }
        }
    });

    document.addEventListener('click', e => {
        if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
            fecharDropdown();
        }
    });
})();
</script>
