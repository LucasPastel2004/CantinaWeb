<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Início do Portal da Família (Painel Executivo dos Responsáveis)
 * 
 * Conforme docs/DESIGN.md, docs/FSD.md e padrão corporativo Anti-Slop Enterprise
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$usuario = $usuario ?? [];
$saldoAtual = $saldoAtual ?? 0.00;
$dependentes = $dependentes ?? [];
$ultimosPedidos = $ultimosPedidos ?? [];
$totalAgendadosAtivos = $totalAgendadosAtivos ?? 0;
$dataExtenso = $dataExtenso ?? date('d/m/Y');
$bloqueadoMed = $bloqueadoMed ?? false;

// Função auxiliar de capitalização elegante para nomes próprios
$formatarNome = function(?string $nome): string {
    $nome = trim((string)$nome);
    if (empty($nome)) {
        return '';
    }
    $preposicoes = ['de', 'da', 'do', 'das', 'dos', 'e'];
    $palavras = explode(' ', mb_strtolower($nome, 'UTF-8'));
    $formatadas = array_map(function($palavra) use ($preposicoes) {
        return in_array($palavra, $preposicoes, true) ? $palavra : mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
    }, $palavras);
    return implode(' ', $formatadas);
};

$nomeFormatado = $formatarNome($responsavelNome ?? $usuario['nome'] ?? 'Responsável');
$primeiroNome = explode(' ', $nomeFormatado)[0];
$isSaldoBaixo = ($saldoAtual < 15.00);
?>

<style>
/* Estilos corporativos refinados exclusivos do Portal da Família */
.portal-hero-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}
.portal-pillar-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.15s ease-in-out;
}
.portal-pillar-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
    transform: translateY(-1px);
}
.portal-pillar-icon {
    width: 36px;
    height: 36px;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.student-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.15s ease-in-out;
}
.student-card:last-child {
    margin-bottom: 0;
}
.student-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
}
.student-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #bfdbfe;
}
.action-tile-portal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none !important;
    transition: all 0.15s ease-in-out;
    margin-bottom: 8px;
}
.action-tile-portal:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.04);
    transform: translateY(-1px);
}
.action-tile-portal .action-tile-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background: #eff6ff;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease-in-out;
}
.action-tile-portal:hover .action-tile-icon {
    background: #eff6ff;
    color: #1d4ed8;
}
.action-tile-portal .action-tile-arrow {
    color: #94a3b8;
    transition: transform 0.15s ease, color 0.15s ease;
    flex-shrink: 0;
}
.action-tile-portal:hover .action-tile-arrow {
    color: #1d4ed8;
    transform: translateX(3px);
}
.table-portal thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid #e2e8f0;
    padding: 9px 12px;
}
.table-portal tbody td {
    padding: 9px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    font-size: 12px;
}
.table-portal tbody tr:hover td {
    background-color: #f8fafc;
}
</style>

<!-- Alerta Especial de Bloqueio Bancário MED (Se Houver) -->
<?php if ($bloqueadoMed): ?>
    <div class="alert alert-danger py-3 px-4 mb-4 border-left-danger" style="border-left: 4px solid #b91c1c; background-color: #fef2f2; color: #991b1b;">
        <h6 class="font-weight-bold mb-1">Atenção: Conta Temporariamente Suspensa (Notificação Bancária)</h6>
        <p class="mb-0 text-microcopy" style="font-size: 13px;">
            Identificamos uma contestação bancária (MED) vinculada à sua carteira familiar. Novos agendamentos estão temporariamente suspensos. Favor comparecer à administração da cantina para regularização.
        </p>
    </div>
<?php endif; ?>

<!-- Cabeçalho Executivo do Portal -->
<div class="portal-hero-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Portal da Família &bull; Colégio Monteiro Lobato</span>
            </div>
            <h1 class="h5 font-weight-bold text-dark mb-1">
                Olá, <?php echo htmlspecialchars($nomeFormatado, ENT_QUOTES, 'UTF-8'); ?>!
            </h1>
            <p class="text-microcopy text-muted mb-0">
                <?php echo $dataExtenso; ?> &bull; 
                <strong><?php echo count($dependentes); ?></strong> dependente(s) vinculado(s) &bull; 
                Carteira familiar ativa
            </p>
        </div>
    </div>
</div>

<!-- Os 4 Pilares da Família (Action Stat Cards) -->
<div class="row g-3 mb-4">

    <!-- Pilar 1: Carteira Familiar & Saldo -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="portal-pillar-card">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2.5">
                    <div class="portal-pillar-icon">
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499L12.136.326zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484L5.562 3zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
                        </svg>
                    </div>
                    <?php if ($isSaldoBaixo): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning font-tabular" style="font-size: 11px;">
                            Alerta Saldo Baixo
                        </span>
                    <?php else: ?>
                        <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">
                            <span class="text-success">&bull;</span> Carteira Ativa
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-microcopy text-secondary font-weight-semibold text-uppercase" style="font-size: 11px;">
                    Saldo Disponível
                </div>
                <div class="font-tabular font-weight-bold mb-1" style="font-size: 24px; color: <?php echo $isSaldoBaixo ? '#ea580c' : '#0f172a'; ?>;">
                    R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                </div>
                <p class="text-microcopy text-muted mb-3" style="font-size: 12px; line-height: 1.4;">
                    Saldo compartilhado automaticamente entre todos os seus filhos.
                </p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                    <span>Recarregar via Pix</span>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Pilar 2: Agendamento da Semana -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="portal-pillar-card">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2.5">
                    <div class="portal-pillar-icon">
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-microcopy text-secondary font-weight-semibold text-uppercase" style="font-size: 11px;">
                    Lanches da Semana
                </div>
                <div class="font-weight-bold mb-1 text-dark" style="font-size: 17px; line-height: 1.35;">
                    Agendamento Escolar
                </div>
                <p class="text-microcopy text-muted mb-3" style="font-size: 12px; line-height: 1.4;">
                    Garanta os lanches favoritos dos seus filhos com antecedência e sem filas.
                </p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                    <span>Acessar Cardápio</span>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Pilar 3: Meus Pedidos & Entregas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="portal-pillar-card">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2.5">
                    <div class="portal-pillar-icon">
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l1.25 5h8.22l1.25-5H3.14zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-microcopy text-secondary font-weight-semibold text-uppercase" style="font-size: 11px;">
                    Histórico & Status
                </div>
                <div class="font-weight-bold mb-1 text-dark" style="font-size: 17px; line-height: 1.35;">
                    Meus Pedidos
                </div>
                <p class="text-microcopy text-muted mb-3" style="font-size: 12px; line-height: 1.4;">
                    Acompanhe pedidos entregues, fichas em sala e edições antes do corte.
                </p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                    <span>Ver Pedidos</span>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Pilar 4: Segurança Alimentar & Alergias -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="portal-pillar-card">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2.5">
                    <div class="portal-pillar-icon">
                        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.464 9.99a11.8 11.8 0 0 0 3.963 3.037.5.5 0 0 0 .464 0c1.248-.68 2.614-1.745 3.963-3.037 1.677-2.195 3.06-5.513 2.464-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0zm0 1.07c.613 0 1.698.257 2.753.543a60 60 0 0 1 2.668.803.54.54 0 0 1 .368.441c.548 4.108-.718 7.155-2.26 9.176a10.8 10.8 0 0 1-3.529 2.705 10.8 10.8 0 0 1-3.529-2.705C2.946 9.972 1.68 6.925 2.228 2.817a.54.54 0 0 1 .368-.442 60 60 0 0 1 2.668-.802C6.302 1.326 7.387 1.07 8 1.07z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-microcopy text-secondary font-weight-semibold text-uppercase" style="font-size: 11px;">
                    Segurança Alimentar
                </div>
                <div class="font-weight-bold mb-1 text-dark" style="font-size: 17px; line-height: 1.35;">
                    Alergias & Restrições
                </div>
                <p class="text-microcopy text-muted mb-3" style="font-size: 12px; line-height: 1.4;">
                    Bloqueio automático de ingredientes e alérgenos em compras presenciais.
                </p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>/portal/alergias" class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                    <span>Configurar Restrições</span>
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Seção Central em Duas Colunas: Dependentes e Central de Acesso Rápido -->
<div class="row g-4 align-items-start mb-4">

    <!-- Coluna 1: Dependentes Vinculados (Cards com Fichas Visuais Modulares) -->
    <div class="col-12 col-lg-7">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold text-dark text-microcopy text-uppercase">Dependentes & Alunos Vinculados</span>
                </div>
                <span class="badge bg-white text-secondary border font-tabular" style="font-size: 11px;">
                    <?php echo count($dependentes); ?> aluno(s) ativo(s)
                </span>
            </div>
            <div class="card-enterprise-body p-3">
                <?php if (empty($dependentes)): ?>
                    <div class="text-center py-5 text-muted text-microcopy">
                        <div class="mb-2">
                            <svg width="40" height="40" viewBox="0 0 16 16" fill="#94a3b8">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                            </svg>
                        </div>
                        <h6 class="font-weight-bold text-dark mb-1">Nenhum dependente vinculado</h6>
                        <p class="mb-0 text-secondary">Os alunos vinculados à sua família são cadastrados e confirmados pela secretaria do colégio.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($dependentes as $dep): ?>
                        <?php 
                            $nomeAluno = $formatarNome($dep['nome'] ?? 'Aluno');
                            $inicial = mb_strtoupper(mb_substr($nomeAluno !== '' ? $nomeAluno : 'A', 0, 1, 'UTF-8'), 'UTF-8');
                            $totalRestricoes = (int)($dep['total_restricoes'] ?? 0);
                        ?>
                        <div class="student-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="student-avatar">
                                    <?php echo $inicial; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                                        <h3 class="h6 font-weight-bold text-dark mb-0" style="font-size: 15px;">
                                            <?php echo htmlspecialchars($nomeAluno, ENT_QUOTES, 'UTF-8'); ?>
                                        </h3>
                                        <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">
                                            Matrícula: <?php echo htmlspecialchars((string)($dep['matricula'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <div class="text-microcopy text-secondary font-tabular mb-2">
                                        Turma: <strong><?php echo htmlspecialchars($dep['nome_turma'] ?? 'A definir', ENT_QUOTES, 'UTF-8'); ?></strong> &bull; 
                                        Turno: <strong><?php echo ucfirst($dep['turno_turma'] ?? $dep['turno'] ?? 'matutino'); ?></strong>
                                    </div>

                                    <!-- Faixa de Alergias & Restrições do Aluno -->
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap mb-3 pt-1 border-top">
                                        <span class="text-microcopy text-muted" style="font-size: 11px;">Segurança Alimentar:</span>
                                        <?php if ($totalRestricoes > 0 && !empty($dep['restricoes'])): ?>
                                            <?php foreach ($dep['restricoes'] as $rest): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning" style="font-size: 10.5px;">
                                                    <?php echo htmlspecialchars($rest['nome_exibicao'] ?? $rest['nome'] ?? 'Restrição', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size: 10.5px;">
                                                Nenhuma restrição cadastrada
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Ações Rápidas do Aluno -->
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                                            </svg>
                                            <span>Agendar Lanche</span>
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/portal/extrato?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                            </svg>
                                            <span>Extrato de Consumo</span>
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.464 9.99a11.8 11.8 0 0 0 3.963 3.037.5.5 0 0 0 .464 0c1.248-.68 2.614-1.745 3.963-3.037 1.677-2.195 3.06-5.513 2.464-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0z"/>
                                            </svg>
                                            <span>Restrições</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna 2: Central de Acesso Rápido -->
    <div class="col-12 col-lg-5">
        <!-- Card de Ações Rápidas (Action Tiles Corporativos) -->
        <div class="card-enterprise">
            <div class="card-enterprise-header py-2.5 px-3 bg-light border-bottom">
                <span class="fw-semibold text-dark text-microcopy text-uppercase">Atalhos da Família & Gestão</span>
            </div>
            <div class="p-3">
                <a href="<?php echo $baseUrl; ?>/portal/carteira" class="action-tile-portal">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="action-tile-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v1h14V2a1 1 0 0 0-1-1H2zm13 3H1v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-weight-semibold text-dark" style="font-size: 13px;">Recarga Rápida Pix</div>
                            <div class="text-microcopy text-secondary">Liberação instantânea com QR Code sem tarifas</div>
                        </div>
                    </div>
                    <div class="action-tile-arrow">&rarr;</div>
                </a>

                <a href="<?php echo $baseUrl; ?>/portal/extrato" class="action-tile-portal">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="action-tile-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M4 0h5.293A1 1 0 0 1 10 .293L13.707 4a1 1 0 0 1 .293.707V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2zm5.5 1.5v2a1 1 0 0 0 1 1h2l-3-3zM4.5 9a.5.5 0 0 0 0 1h7a.5.5 0 0 0 0-1h-7zM4 11.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 0-1h-7a.5.5 0 0 0-.5.5z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-weight-semibold text-dark" style="font-size: 13px;">Extrato Detalhado do Aluno</div>
                            <div class="text-microcopy text-secondary">Espelho para impressão A4 e consulta por período</div>
                        </div>
                    </div>
                    <div class="action-tile-arrow">&rarr;</div>
                </a>

                <a href="<?php echo $baseUrl; ?>/portal/perfil" class="action-tile-portal mb-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="action-tile-icon">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-weight-semibold text-dark" style="font-size: 13px;">Meus Dados & Segurança</div>
                            <div class="text-microcopy text-secondary">Atualize seu e-mail de contato e senha</div>
                        </div>
                    </div>
                    <div class="action-tile-arrow">&rarr;</div>
                </a>
            </div>
        </div>
    </div>

</div>
