<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Início do Painel Operacional e Administrativo da Cantina (Visão Geral)
 * 
 * Conforme docs/DESIGN.md e diretrizes do Padrão Escolar Manager
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$usuario = $usuario ?? [];
$turnoAtivo = $turnoAtivo ?? 'matutino';
$statusCaixa = $statusCaixa ?? 'Fechado';
$horarioCorte = $horarioCorte ?? '08:00';
$horarioCaixa = $horarioCaixa ?? 'Fechamento 11:00';

// Formatação amigável da data atual em português
$diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$mesesAno = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$diaSemanaStr = $diasSemana[(int)date('w')];
$diaStr = date('d');
$mesStr = $mesesAno[(int)date('n')];
$anoStr = date('Y');
$dataExtenso = "{$diaSemanaStr}, {$diaStr} de {$mesStr} de {$anoStr}";
?>

<style>
/* Estilos corporativos refinados dos cards de atalhos e tabela operacional */
.action-tile-admin {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none !important;
    transition: all 0.15s ease-in-out;
}
.action-tile-admin:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    box-shadow: 0 2px 5px rgba(15, 23, 42, 0.05);
    transform: translateY(-1px);
}
.action-tile-icon {
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
.action-tile-admin:hover .action-tile-icon {
    background: #1d4ed8;
    color: #ffffff;
}
.action-tile-arrow {
    color: #94a3b8;
    transition: transform 0.15s ease, color 0.15s ease;
    flex-shrink: 0;
}
.action-tile-admin:hover .action-tile-arrow {
    color: #1d4ed8;
    transform: translateX(3px);
}

.table-grade-horarios {
    margin-bottom: 0;
}
.table-grade-horarios thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid #e2e8f0;
    padding: 7px 10px;
}
.table-grade-horarios tbody td {
    padding: 7px 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    font-size: 12px;
}
.table-grade-horarios tbody tr:hover td {
    background-color: #f8fafc;
}
.table-grade-horarios tbody tr.turno-vigente td {
    background-color: #f0f7ff;
}
.table-grade-horarios tbody tr:last-child td {
    border-bottom: none;
}
</style>

<div class="container-fluid px-0 pb-4">

    <!-- Cabeçalho Executivo Limpo (sem botões redundantes no topo) -->
    <div class="mb-4 pb-2 border-bottom">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Operacional & Gestão da Cantina</span>
            <span class="badge bg-light text-secondary border font-tabular">Padrão Escolar Manager</span>
        </div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Painel da Cantina &mdash; Visão Geral</h1>
        <p class="text-microcopy text-muted mb-0">
            Olá, <strong><?php echo htmlspecialchars($usuario['nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo ucfirst($usuario['perfil'] ?? $usuario['role'] ?? 'Operador'); ?>) &bull; <?php echo $dataExtenso; ?> &bull; Turno <strong><?php echo ucfirst($turnoAtivo); ?></strong>
        </p>
    </div>

    <!-- Seção Principal: Os 4 Pilares da Operação da Cantina -->
    <div class="row g-3 mb-4">
        
        <!-- Pilar 1: Ponto de Venda (PDV Balcão) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-enterprise h-100 d-flex flex-column justify-content-between p-3" style="transition: border-color 0.15s, box-shadow 0.15s;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2.5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded" style="width: 36px; height: 36px; background: #eff6ff; color: #1d4ed8;">
                            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5zM3.14 5l1.25 5h8.22l1.25-5H3.14zM5 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                            </svg>
                        </div>
                        <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">Frente de Caixa</span>
                    </div>
                    <h2 class="h6 font-weight-bold text-dark mb-1" style="font-size: 14px;">Ponto de Venda (PDV)</h2>
                    <p class="text-microcopy text-secondary mb-3" style="font-size: 12px; line-height: 1.45;">
                        Vendas avulsas no recreio com cálculo automático de troco e consumo de colaboradores com foto e desconto em folha.
                    </p>
                </div>
                <div>
                    <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                        <span>Abrir PDV</span>
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pilar 2: Corte do Turno & Encomendas -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-enterprise h-100 d-flex flex-column justify-content-between p-3" style="transition: border-color 0.15s, box-shadow 0.15s;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2.5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded" style="width: 36px; height: 36px; background: #eff6ff; color: #1d4ed8;">
                            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                        </div>
                        <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">Corte: <?php echo $horarioCorte; ?></span>
                    </div>
                    <h2 class="h6 font-weight-bold text-dark mb-1" style="font-size: 14px;">Corte de Fornecedor</h2>
                    <p class="text-microcopy text-secondary mb-3" style="font-size: 12px; line-height: 1.45;">
                        Consolidação matemática de salgados encomendados pontualmente no corte para pedido com fornecedor sem desperdício.
                    </p>
                </div>
                <div>
                    <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                        <span>Acessar Corte</span>
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pilar 3: Tickets Térmicos -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-enterprise h-100 d-flex flex-column justify-content-between p-3" style="transition: border-color 0.15s, box-shadow 0.15s;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2.5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded" style="width: 36px; height: 36px; background: #eff6ff; color: #1d4ed8;">
                            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                            </svg>
                        </div>
                        <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">Bobina 80mm</span>
                    </div>
                    <h2 class="h6 font-weight-bold text-dark mb-1" style="font-size: 14px;">Tickets Térmicos</h2>
                    <p class="text-microcopy text-secondary mb-3" style="font-size: 12px; line-height: 1.45;">
                        Impressão contínua em bobina de 80mm por série e turma para entrega em sala e reemissão de 2ª via com auditoria.
                    </p>
                </div>
                <div>
                    <a href="<?php echo $baseUrl; ?>/operacional/tickets" class="btn btn-enterprise btn-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                        <span>Imprimir Tickets</span>
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pilar 4: Sessão de Caixa -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-enterprise h-100 d-flex flex-column justify-content-between p-3" style="transition: border-color 0.15s, box-shadow 0.15s;">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2.5">
                        <div class="d-inline-flex align-items-center justify-content-center rounded" style="width: 36px; height: 36px; background: #eff6ff; color: #1d4ed8;">
                            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                                <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                            </svg>
                        </div>
                        <span class="badge <?php echo ($statusCaixa === 'Aberto') ? 'bg-success text-white' : 'bg-light text-secondary border'; ?> font-tabular" style="font-size: 11px;">
                            <?php echo ($statusCaixa === 'Aberto') ? 'Caixa Aberto' : 'Caixa Fechado'; ?>
                        </span>
                    </div>
                    <h2 class="h6 font-weight-bold text-dark mb-1" style="font-size: 14px;">Sessão de Caixa</h2>
                    <p class="text-microcopy text-secondary mb-3" style="font-size: 12px; line-height: 1.45;">
                        Abertura obrigatória com fundo de troco, prestação de contas cega e fechamento por turno (11:00 / 16:00).
                    </p>
                </div>
                <div>
                    <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="btn btn-enterprise btn-secondary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-1.5">
                        <span>Gerenciar Caixa</span>
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Seção Inferior em Duas Colunas: Grade Horária Oficial e Central de Atalhos Polidos -->
    <div class="row g-3">
        
        <!-- Coluna 1: Grade Operacional e Linha do Tempo dos Turnos -->
        <div class="col-12 col-lg-7">
            <div class="card-enterprise h-100 d-flex flex-column">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                    <span class="fw-semibold text-dark text-microcopy text-uppercase">Rotina Operacional &bull; Grade de Horários Oficiais</span>
                    <span class="badge bg-white text-secondary border font-tabular" style="font-size: 11px;">Turno Atual: <?php echo ucfirst($turnoAtivo); ?></span>
                </div>
                <div class="card-enterprise-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                    <div class="table-responsive">
                        <table class="table table-sm table-grade-horarios align-middle font-tabular">
                            <thead>
                                <tr>
                                    <th style="width: 75px;" class="text-center">HORÁRIO</th>
                                    <th style="width: 95px;">TURNO</th>
                                    <th>MARCO OPERACIONAL</th>
                                    <th style="width: 115px;" class="text-center">SITUAÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr <?php echo ($turnoAtivo === 'matutino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">08:00</td>
                                    <td>Matutino</td>
                                    <td>Bloqueio rígido de corte (encomendas do dia)</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Corte Matutino</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'matutino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">09:30</td>
                                    <td>Matutino</td>
                                    <td>Separação e entrega dos tickets térmicos em sala</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Entrega Salas</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'matutino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">10:00</td>
                                    <td>Matutino</td>
                                    <td>Intervalo / Recreio (atendimento visual por tickets)</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Recreio 20 min</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'matutino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">11:00</td>
                                    <td>Matutino</td>
                                    <td>Fechamento de caixa matutino e prestação de contas</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Caixa 1º Turno</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'vespertino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">13:30</td>
                                    <td>Vespertino</td>
                                    <td>Bloqueio rígido de corte (encomendas do dia)</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Corte Vespertino</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'vespertino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">15:00</td>
                                    <td>Vespertino</td>
                                    <td>Separação e entrega dos tickets térmicos em sala</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Entrega Salas</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'vespertino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">15:30</td>
                                    <td>Vespertino</td>
                                    <td>Intervalo / Recreio (atendimento visual por tickets)</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Recreio 20 min</span>
                                    </td>
                                </tr>
                                <tr <?php echo ($turnoAtivo === 'vespertino') ? 'class="turno-vigente"' : ''; ?>>
                                    <td class="text-center font-weight-bold">16:00</td>
                                    <td>Vespertino</td>
                                    <td>Fechamento de caixa vespertino e prestação de contas</td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10.5px;">Caixa 2º Turno</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2.5 bg-light rounded border mt-3 d-flex align-items-center gap-2">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-primary flex-shrink-0">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        <span class="text-secondary text-microcopy" style="font-size: 11.5px; line-height: 1.4;">
                            <strong>Protocolo Anti-Filas:</strong> Recreio com atendimento 100% visual por tickets térmicos pré-impressos, eliminando filas e dinheiro físico entre crianças.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna 2: Central de Atalhos Rápidos da Retaguarda (Polidos e Elegantes) -->
        <div class="col-12 col-lg-5">
            <div class="card-enterprise h-100 d-flex flex-column">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                    <span class="fw-semibold text-dark text-microcopy text-uppercase">Atalhos de Gestão & Retaguarda</span>
                    <span class="badge bg-white text-secondary border" style="font-size: 11px;">Acesso Rápido</span>
                </div>
                <div class="card-enterprise-body p-3 d-flex flex-column justify-content-between flex-grow-1">
                    <div class="row g-2.5">
                        
                        <!-- Atalho 1: Entrada de Mercadorias -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/estoque/entrada" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">Entrada de Estoque</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Reposição de itens</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Atalho 2: Estorno de Vendas -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/operacional/estornos" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/>
                                            <path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">Estorno de Vendas</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Auditoria e estornos</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Atalho 3: Vendas por Alimento -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-4zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">Vendas & Alimentos</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Curva ABC e relatórios</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Atalho 4: Livro Caixa Diário -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/relatorios/livro-caixa" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">Livro Caixa Diário</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Extrato contábil 13 cols</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Atalho 5: DRE Gerencial -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/financeiro/dre" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h3a1 1 0 0 0 1-1v-2zm-5 3v-3H6v3h4zm-5 0v-3H1v2a1 1 0 0 0 1 1h3zm-4-4h4V8H1v3zm0-4h4V4H1v3zm5-3v3h4V4H6zm4 4H6v3h4V8z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">DRE Gerencial</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Caixa e competência</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                        <!-- Atalho 6: Fornecedores Parceiros -->
                        <div class="col-12 col-sm-6">
                            <a href="<?php echo $baseUrl; ?>/admin/fornecedores" class="action-tile-admin">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="action-tile-icon">
                                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                            <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                            <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <span class="d-block font-weight-bold text-dark text-truncate" style="font-size: 12.5px;">Fornecedores</span>
                                        <span class="text-muted d-block text-truncate" style="font-size: 11px;">Parceiros e recibos</span>
                                    </div>
                                </div>
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" class="action-tile-arrow">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Rodapé Institucional Enxuto -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-microcopy text-muted pt-3 mt-3 border-top">
        <span>Colégio Monteiro Lobato &bull; Sistema Cantina Web &bull; Visão Geral</span>
        <span class="font-tabular">Valores para o Futuro! &bull; &copy; <?php echo date('Y'); ?></span>
    </div>

</div>
