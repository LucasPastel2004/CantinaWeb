<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Painel de Filtros Avançados de Vendas da Cantina
 * 
 * Conforme docs/alterações sistema guideline/Filtros de vendas.pdf, 
 * relatorio de vendas por período.pdf, docs/DESIGN.md e padrão visual Escolar Manager
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$filtros = $filtros ?? [];
$categorias = $categorias ?? [];

$dataInicio = $filtros['data_inicio'] ?? date('Y-m-d');
$dataFim    = $filtros['data_fim'] ?? date('Y-m-d');
$turno      = $filtros['turno'] ?? '';
$categoriaId = (int)($filtros['categoria_id'] ?? 0);

$emitirObs    = ($filtros['emitir_observacao'] ?? 'nao') === 'sim';
$emitirForma  = ($filtros['emitir_forma_pagamento'] ?? 'nao') === 'sim';
$incluirEst   = ($filtros['incluir_estornos'] ?? 'nao') === 'sim';
$agruparTipo  = ($filtros['agrupar_tipo_pessoa'] ?? 'nao') === 'sim';
$agruparPer   = ($filtros['agrupar_periodo'] ?? 'sim') === 'sim';
$emitirHora   = ($filtros['emitir_horario'] ?? 'nao') === 'sim';

$emitirAlim   = ($filtros['emitir_vendas_alimento'] ?? 'sim') !== 'nao';
$agruparAlim  = ($filtros['agrupar_alimento'] ?? 'sim') !== 'nao';
$ordenarAlim  = $filtros['ordenar_alimentos'] ?? 'mais_vendidos';

$emitirAluno  = ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim';
$agruparAluno = ($filtros['agrupar_vendas_aluno'] ?? 'nao') === 'sim';
$agruparTurma = ($filtros['agrupar_serie_turma'] ?? 'nao') === 'sim';
$quebrarTurma = ($filtros['quebrar_pagina_turma'] ?? 'nao') === 'sim';
$ordConsumo   = ($filtros['ordenar_consumo_aluno'] ?? 'nao') === 'sim';
$ordPor       = $filtros['ordenar_por'] ?? 'maior_consumo';
?>

<style>
/* Estilos corporativos específicos do painel de emissão Escolar Manager */
.nav-tabs-enterprise {
    border-bottom: 2px solid #e2e8f0;
    gap: 4px;
}
.nav-tabs-enterprise .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    background: transparent;
    border-radius: 4px 4px 0 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease-in-out;
}
.nav-tabs-enterprise .nav-link:hover {
    color: #1d4ed8;
    background: #f8fafc;
}
.nav-tabs-enterprise .nav-link.active {
    color: #ffffff;
    background: #1d4ed8;
    border-color: #1d4ed8;
    border-radius: 6px 6px 0 0;
    box-shadow: 0 1px 3px rgba(29, 78, 216, 0.25);
}

/* Grupo de botões segmentados com destaque ativo azul corporativo */
.btn-toggle-group-escolar {
    display: inline-flex;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    overflow: hidden;
}
.btn-toggle-group-escolar .btn-check:checked + .btn-escolar-segment {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 600;
}
.btn-toggle-group-escolar .btn-escolar-segment {
    background-color: #ffffff;
    border: none;
    border-right: 1px solid #e2e8f0;
    color: #475569;
    font-size: 12px;
    padding: 6px 14px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    border-radius: 0;
}
.btn-toggle-group-escolar .btn-escolar-segment:last-child {
    border-right: none;
}
.btn-toggle-group-escolar .btn-escolar-segment:hover:not(.disabled):not(:disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
}
.btn-toggle-group-escolar .btn-escolar-segment.disabled,
.btn-toggle-group-escolar .btn-escolar-segment:disabled {
    background-color: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Compatibilidade com seletores corporativos */
.btn-toggle-blue .btn-check:checked + .btn-outline-custom {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 600;
}

.card-filtro-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.label-campo-escolar {
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 6px;
    display: block;
}
.btn-action-emitir {
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 14px;
}
</style>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Formulário Oficial de Filtros -->
    <form action="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir" method="GET" target="_blank" id="formFiltrosVendas">

        <!-- Cabeçalho Executivo e Barra de Identificação -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Relatórios Gerenciais</span>
                    <span class="badge bg-light text-secondary border font-tabular">Padrão Escolar Manager</span>
                </div>
                <h1 class="h5 font-weight-bold text-dark mb-0">Filtros de Vendas & Relatório por Alimento</h1>
            </div>
        </div>

        <!-- Abas de Navegação Estilo Escolar Manager: Filtro e Layout -->
        <ul class="nav nav-tabs nav-tabs-enterprise mb-3" id="tabsVendas" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-filtro-btn" data-bs-toggle="tab" data-bs-target="#tab-filtro-pane" type="button" role="tab" aria-controls="tab-filtro-pane" aria-selected="true">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
                    </svg>
                    <span>Filtro</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-layout-btn" data-bs-toggle="tab" data-bs-target="#tab-layout-pane" type="button" role="tab" aria-controls="tab-layout-pane" aria-selected="false">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                    </svg>
                    <span>Layout</span>
                </button>
            </li>
        </ul>

        <!-- Conteúdo das Abas -->
        <div class="tab-content" id="tabsVendasContent">

            <!-- ABA 1: FILTROS DE DADOS -->
            <div class="tab-pane fade show active" id="tab-filtro-pane" role="tabpanel" aria-labelledby="tab-filtro-btn" tabindex="0">
                <div class="card-filtro-container mb-3">

                    <!-- Linha 1: Período com Atalhos Rápidos Alinhados -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="dataInicio" class="label-campo-escolar">
                                Período: Data Inicial
                            </label>
                            <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="dataFim" class="label-campo-escolar">
                                Período: Data Final
                            </label>
                            <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-12 col-md-6 d-flex align-items-end flex-wrap gap-1">
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('hoje')">Hoje</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('ontem')">Ontem</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('esta_semana')">Esta Semana</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('este_mes')">Este Mês</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
                        </div>
                    </div>

                    <hr class="my-3 text-muted" style="opacity: 0.15;">

                    <!-- Linha 2: Turno Escolar e Categoria do Cardápio -->
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filtroTurno" class="label-campo-escolar">
                                Turno Escolar
                            </label>
                            <select name="turno" id="filtroTurno" class="form-select form-select-sm font-tabular">
                                <option value="">-- Todos os Turnos --</option>
                                <option value="matutino" <?php echo ($turno === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                                <option value="vespertino" <?php echo ($turno === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="filtroCategoria" class="label-campo-escolar">
                                Categoria do Cardápio
                            </label>
                            <select name="categoria_id" id="filtroCategoria" class="form-select form-select-sm font-tabular">
                                <option value="0">-- Todas as Categorias --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoriaId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ABA 2: LAYOUT E FORMATAÇÃO (ESTILO ESCOLAR MANAGER) -->
            <div class="tab-pane fade" id="tab-layout-pane" role="tabpanel" aria-labelledby="tab-layout-btn" tabindex="0">
                <div class="card-filtro-container mb-3">

                    <!-- Cabeçalho de Controle de Layout com Redefinir Padrões -->
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                        <span class="label-campo-escolar mb-0" style="color: #1e293b; font-size: 12px;">Parâmetros de Emissão & Formatação</span>
                        <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;">
                            Redefinir Padrões
                        </a>
                    </div>

                    <!-- Bloco Geral: 2 Linhas de 3 Colunas (Simetria Perfeita) -->
                    <div class="row g-3 mb-3">
                        <!-- 1. Emitir observação -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Emitir observação</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="emitir_observacao" id="obs_sim" value="sim" <?php echo $emitirObs ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="obs_sim">Sim</label>

                                <input type="radio" class="btn-check" name="emitir_observacao" id="obs_nao" value="nao" <?php echo !$emitirObs ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="obs_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Exibe observações lançadas no pedido
                            </small>
                        </div>

                        <!-- 2. Emitir forma de pagamento -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Emitir forma de pagamento</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_sim" value="sim" <?php echo $emitirForma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="forma_sim">Sim</label>

                                <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_nao" value="nao" <?php echo !$emitirForma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="forma_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Discrimina dinheiro, Pix ou carteira
                            </small>
                        </div>

                        <!-- 3. Incluir estorno de vendas -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Incluir estorno de vendas</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_sim" value="sim" <?php echo $incluirEst ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="estorno_sim">Sim</label>

                                <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_nao" value="nao" <?php echo !$incluirEst ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="estorno_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Inclui registros estornados auditados
                            </small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- 4. Agrupar por tipo pessoa -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Agrupar por tipo pessoa</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_sim" value="sim" <?php echo $agruparTipo ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="tipo_pessoa_sim">Sim</label>

                                <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_nao" value="nao" <?php echo !$agruparTipo ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="tipo_pessoa_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Segrega consumo de alunos e colaboradores
                            </small>
                        </div>

                        <!-- 5. Agrupar consumo por período -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Agrupar consumo por período</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_sim" value="sim" <?php echo $agruparPer ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="periodo_sim">Sim</label>

                                <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_nao" value="nao" <?php echo !$agruparPer ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="periodo_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Consolida totais por dia no período
                            </small>
                        </div>

                        <!-- 6. Emitir horário da venda -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Emitir horário da venda</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="emitir_horario" id="horario_sim" value="sim" <?php echo $emitirHora ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="horario_sim">Sim</label>

                                <input type="radio" class="btn-check" name="emitir_horario" id="horario_nao" value="nao" <?php echo !$emitirHora ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="horario_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Exibe carimbo de data e hora do registro
                            </small>
                        </div>
                    </div>

                    <!-- Bloco Alimentos: 1 Linha de 3 Colunas (Simetria Perfeita) -->
                    <div class="row g-3 mb-3 pt-3 border-top">
                        <!-- 7. Emitir vendas por alimento -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Emitir vendas por alimento</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_sim" value="sim" <?php echo $emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="alim_sim">Sim</label>

                                <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_nao" value="nao" <?php echo !$emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="alim_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Detalhamento de produtos vendidos
                            </small>
                        </div>

                        <!-- 8. Agrupar por alimento -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Agrupar por alimento</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_sim" value="sim" <?php echo $agruparAlim ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="agrup_alim_sim">Sim</label>

                                <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_nao" value="nao" <?php echo !$agruparAlim ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="agrup_alim_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Totaliza unidades por alimento no período
                            </small>
                        </div>

                        <!-- 9. Ordenar por alimentos -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Ordenar por alimentos</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_menos" value="menos_vendidos" <?php echo ($ordenarAlim === 'menos_vendidos') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_alim_menos">Menos vendidos</label>

                                <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_mais" value="mais_vendidos" <?php echo ($ordenarAlim === 'mais_vendidos') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_alim_mais">Mais vendidos</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Critério de ordenação de saída de itens
                            </small>
                        </div>
                    </div>

                    <!-- Bloco Alunos & Turmas: 2 Linhas de 3 Colunas (Simetria Perfeita) -->
                    <div class="row g-3 mb-3 pt-3 border-top">
                        <!-- 10. Emitir vendas por aluno -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Emitir vendas por aluno</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_sim" value="sim" <?php echo $emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="aluno_sim">Sim</label>

                                <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_nao" value="nao" <?php echo !$emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="aluno_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Detalhamento nominal por aluno
                            </small>
                        </div>

                        <!-- 11. Agrupar vendas por aluno -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Agrupar vendas por aluno</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_sim" value="sim" <?php echo $agruparAluno ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="agrup_aluno_sim">Sim</label>

                                <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_nao" value="nao" <?php echo !$agruparAluno ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="agrup_aluno_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Totaliza consumo individual por aluno
                            </small>
                        </div>

                        <!-- 12. Agrupar por série/turma -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Agrupar por série/turma</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_sim" value="sim" <?php echo $agruparTurma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="turma_sim">Sim</label>

                                <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_nao" value="nao" <?php echo !$agruparTurma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="turma_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Segrega lançamentos por turma escolar
                            </small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- 13. Quebrar página por série/turma -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Quebrar página por turma</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_sim" value="sim" <?php echo $quebrarTurma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="quebra_sim">Sim</label>

                                <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_nao" value="nao" <?php echo !$quebrarTurma ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="quebra_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Insere nova página a cada turma
                            </small>
                        </div>

                        <!-- 14. Ordenar por consumo do aluno -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Ordenar por consumo do aluno</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_sim" value="sim" <?php echo $ordConsumo ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_consumo_sim">Sim</label>

                                <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_nao" value="nao" <?php echo !$ordConsumo ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_consumo_nao">Não</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Classifica alunos pelo volume de compras
                            </small>
                        </div>

                        <!-- 15. Critério de ordenação -->
                        <div class="col-12 col-md-4">
                            <label class="label-campo-escolar">Critério de Ordenação</label>
                            <div class="btn-toggle-group-escolar btn-toggle-blue w-100">
                                <input type="radio" class="btn-check" name="ordenar_por" id="ord_menor" value="menor_consumo" <?php echo ($ordPor === 'menor_consumo') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_menor">Menor consumo</label>

                                <input type="radio" class="btn-check" name="ordenar_por" id="ord_maior" value="maior_consumo" <?php echo ($ordPor === 'maior_consumo') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment btn-outline-custom flex-fill text-center" for="ord_maior">Maior consumo</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Ordem crescente ou decrescente de consumo
                            </small>
                        </div>
                    </div>

                    <!-- Linha de Dica de Otimização de Impressão Expandida -->
                    <div class="row pt-3 border-top mt-3">
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border w-100">
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                    <span class="text-dark fw-semibold" style="font-size: 12.5px;">Dica de Otimização de Impressão:</span>
                                </div>
                                <p class="text-secondary mb-0 mt-1" style="font-size: 12px; line-height: 1.4;">
                                    A combinação de <strong>Agrupar por Alimento</strong> com <strong>Mais Vendidos</strong> destaca os itens de maior saída para planejamento de compras e cardápio. Para conferência de distribuição e organização em sala, habilite <strong>Agrupar por Série/Turma</strong> e <strong>Quebrar Página por Turma</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Rodapé com as Ações Oficiais de Emissão -->
        <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Imprimir / Salvar PDF (A4) -->
                <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir" formtarget="_blank" class="btn btn-enterprise btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1.5 btn-action-emitir" id="btnEmitirImprimir">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    <span>Imprimir / Salvar PDF (A4)</span>
                </button>

                <!-- Exportar Planilha CSV -->
                <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/csv" class="btn btn-enterprise btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1.5 btn-action-emitir" id="btnEmitirCsv">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        <path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                    <span>Exportar Planilha (CSV)</span>
                </button>
            </div>
        </div>

    </form>

    <!-- Rodapé Institucional Enxuto -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-microcopy text-muted pt-2 border-top">
        <span>Cantina Web &bull; Colégio Monteiro Lobato &bull; Módulo de Relatórios de Vendas</span>
        <span class="font-tabular">Emissão Oficial A4 &bull; Impressão / Salvar PDF</span>
    </div>

</div>

<script>
// Utilitário de atalhos rápidos de período no padrão Escolar Manager
function definirPeriodo(tipo) {
    const hoje = new Date();
    let dInicio = new Date();
    let dFim = new Date();

    if (tipo === 'hoje') {
        // dInicio e dFim são hoje
    } else if (tipo === 'ontem') {
        dInicio.setDate(hoje.getDate() - 1);
        dFim.setDate(hoje.getDate() - 1);
    } else if (tipo === 'esta_semana') {
        const diaSemana = hoje.getDay(); // 0 domingo, 1 segunda
        const diffSegunda = diaSemana === 0 ? -6 : 1 - diaSemana;
        dInicio.setDate(hoje.getDate() + diffSegunda);
    } else if (tipo === 'este_mes') {
        dInicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    } else if (tipo === 'mes_anterior') {
        dInicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        dFim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
    }

    const fmt = (d) => {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    };

    const strIni = fmt(dInicio);
    const strFim = fmt(dFim);

    definirDatas(strIni, strFim);
}

// Compatibilidade direta com chamadas legadas
function definirDatas(inicio, fim) {
    const elIni = document.getElementById('dataInicio');
    const elFim = document.getElementById('dataFim');
    if (elIni) elIni.value = inicio;
    if (elFim) elFim.value = fim;
}

function atualizarEstadoAlimento() {
    const alimSim = document.getElementById('alim_sim').checked;
    const agrupSim = document.getElementById('agrup_alim_sim');
    const agrupNao = document.getElementById('agrup_alim_nao');
    const ordMenos = document.getElementById('ord_alim_menos');
    const ordMais = document.getElementById('ord_alim_mais');

    if (agrupSim) agrupSim.disabled = !alimSim;
    if (agrupNao) agrupNao.disabled = !alimSim;
    if (ordMenos) ordMenos.disabled = !alimSim;
    if (ordMais) ordMais.disabled = !alimSim;
}

function atualizarEstadoAluno() {
    const alunoSim = document.getElementById('aluno_sim').checked;
    const inputsAluno = [
        'agrup_aluno_sim', 'agrup_aluno_nao',
        'turma_sim', 'turma_nao',
        'quebra_sim', 'quebra_nao',
        'ord_consumo_sim', 'ord_consumo_nao',
        'ord_menor', 'ord_maior'
    ];

    inputsAluno.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !alunoSim;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarEstadoAlimento();
    atualizarEstadoAluno();
});
</script>
