<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Analítico de Consumo do Aluno (Portal da Família)
 * 
 * Conforme docs/alterações sistema guideline/Relatório Extrato Consumo Aluno.pdf,
 * FSD Área 1 & Área 6 e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dependentes = $dependentes ?? [];
$alunoSelecionado = $alunoSelecionado ?? ($dependentes[0] ?? null);
$alunoId = (int)($alunoSelecionado['id'] ?? 0);
$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-d');
$jaFiltrado = (bool)($jaFiltrado ?? false);
$filtroGeral = (bool)($filtroGeral ?? false);

$saldoInicial = (float)($dadosExtrato['saldo_inicial'] ?? 0.0);
$saldoAtual = (float)($dadosExtrato['saldo_atual'] ?? ($saldoAtual ?? 0.0));
$totalRecargas = (float)($dadosExtrato['total_recargas'] ?? 0.0);
$totalGasto = (float)($dadosExtrato['total_gasto'] ?? ($dadosExtrato['total_consumo'] ?? 0.0));
$totalLanches = (int)($dadosExtrato['total_lanches'] ?? 0);
$linhas = $dadosExtrato['linhas'] ?? ($dadosExtrato['movimentacoes'] ?? []);
?>

<div class="portal-extrato-container">

    <!-- Cabeçalho Executivo do Portal -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <h1 class="page-title mb-0" style="font-size: 18px; font-weight: 700; color: #0f172a;">Extrato Analítico do Aluno</h1>
        </div>
        <?php if ($alunoSelecionado): ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?php echo $baseUrl; ?>/portal/extrato/imprimir?aluno_id=<?php echo $alunoId; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?><?php echo !empty($filtroGeral) ? '&filtro_geral=1' : ''; ?>" 
                   target="_blank" 
                   class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5"
                   title="Imprimir ou Salvar Extrato Oficial do Aluno em PDF (A4) pelo Navegador">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    Imprimir / Salvar PDF
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seletor de Dependentes (caso haja mais de um filho vinculado) -->
    <?php if (count($dependentes) > 1): ?>
        <div class="mb-4">
            <span class="text-secondary text-microcopy text-uppercase font-weight-bold d-block mb-2">Filho(a) / Dependente:</span>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($dependentes as $dep): ?>
                    <?php $isActive = ((int)$dep['id'] === $alunoId); ?>
                    <a href="<?php echo $baseUrl; ?>/portal/extrato?aluno_id=<?php echo (int)$dep['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
                       class="btn btn-enterprise <?php echo $isActive ? 'btn-primary' : 'btn-secondary'; ?> btn-sm d-inline-flex align-items-center gap-2">
                        <span><?php echo htmlspecialchars($dep['nome']); ?></span>
                        <span class="badge <?php echo $isActive ? 'bg-white text-primary' : 'bg-light text-secondary border'; ?> font-tabular" style="font-size: 11px;">
                            <?php echo htmlspecialchars($dep['serie'] ?? ($dep['nome_turma'] ?? '')); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Barra de Filtros de Período (Padrão de Carga Sob Demanda) -->
    <div class="card-enterprise p-3 mb-4">
        <form action="<?php echo $baseUrl; ?>/portal/extrato" method="GET" class="row g-3 align-items-end" id="formFiltroExtrato">
            <input type="hidden" name="aluno_id" value="<?php echo $alunoId; ?>">
            <input type="hidden" name="filtrar" value="1">
            <input type="hidden" name="filtro_geral" id="filtroGeral" value="<?php echo !empty($filtroGeral) ? '1' : ($jaFiltrado ? '0' : '1'); ?>">

            <div class="col-12 col-md-4">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio); ?>" onchange="marcarFiltroAlterado()">
            </div>

            <div class="col-12 col-md-4">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim); ?>" onchange="marcarFiltroAlterado()">
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar Período
                </button>
                <?php if (!empty($jaFiltrado)): ?>
                    <a href="<?php echo $baseUrl; ?>/portal/extrato?aluno_id=<?php echo $alunoId; ?>" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar filtros e retornar à visualização inicial">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Atalhos de Período -->
        <div class="d-flex align-items-center gap-1.5 mt-2 pt-2 border-top flex-wrap">
            <span class="text-secondary text-microcopy me-1">Atalhos:</span>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('todos')">Todos / Geral</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('hoje')">Hoje</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('semana')">Esta Semana</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('mes_atual')">Este Mês</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
        </div>
    </div>

    <?php if ($alunoSelecionado): ?>
        <!-- Card de Identificação do Filho -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-body d-flex align-items-center justify-content-between flex-wrap gap-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary font-weight-bold font-tabular" style="width: 44px; height: 44px; font-size: 16px;">
                        <?php echo htmlspecialchars(strtoupper(substr($alunoSelecionado['nome'] ?? 'A', 0, 1))); ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="h6 font-weight-bold text-dark mb-0"><?php echo htmlspecialchars(mb_strtoupper($alunoSelecionado['nome'], 'UTF-8')); ?></h2>
                            <span class="code-tag font-tabular" style="font-size: 11px;">
                                Matrícula: <?php echo htmlspecialchars($alunoSelecionado['matricula'] ?? '-'); ?>
                            </span>
                            <span class="code-tag font-tabular" style="font-size: 11px;">
                                <?php echo htmlspecialchars(($alunoSelecionado['serie'] ?? '') . ' ' . ($alunoSelecionado['nome_turma'] ?? '')); ?> (<?php echo htmlspecialchars(ucfirst($alunoSelecionado['turma_turno'] ?? ($alunoSelecionado['turno'] ?? 'matutino'))); ?>)
                            </span>
                        </div>
                        <div class="text-secondary text-microcopy mt-1">
                            <?php if (!empty($jaFiltrado) && !empty($filtroGeral)): ?>
                                Período: <strong class="font-tabular text-dark">Todo o Histórico (Geral)</strong>
                            <?php elseif (!empty($jaFiltrado)): ?>
                                Período: <strong class="font-tabular text-dark"><?php echo date('d/m/Y', strtotime($dataInicio)); ?></strong> a <strong class="font-tabular text-dark"><?php echo date('d/m/Y', strtotime($dataFim)); ?></strong>
                            <?php else: ?>
                                Posição contábil atualizada
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Saldo Atual da Família</span>
                    <span class="font-tabular h5 font-weight-bold text-primary mb-0">
                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </span>
                    <span class="text-microcopy text-muted d-block">Disponível para agendamentos</span>
                </div>
            </div>
        </div>

        <!-- Tabela Analítica Oficial com Saldo Progressivo Linha a Linha (Padrão Corporativo de Alta Densidade) -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">Extrato Analítico Linha a Linha</h2>
                </div>
                <span class="badge bg-light text-secondary border font-tabular">
                    <?php echo !empty($jaFiltrado) ? count($linhas) : 0; ?> lançamento(s)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-enterprise table-data-grid table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 12%;">Data</th>
                            <th class="cell-left" style="width: 36%;">Descrição</th>
                            <th class="cell-center" style="width: 8%;">Qtd</th>
                            <th class="cell-right" style="width: 12%;">V. Unitário</th>
                            <th class="cell-right" style="width: 12%;">V. Total</th>
                            <th class="cell-center" style="width: 10%;">Forma Pgto</th>
                            <th class="cell-right" style="width: 10%;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jaFiltrado)): ?>
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <?php 
                                        $emptyMessage = "Não há nada para mostrar aqui";
                                        $emptyDescription = "Clique no botão azul \"Filtrar Período\" para exibir a lista geral de tudo ou defina datas específicas nos filtros.";
                                        require __DIR__ . '/../partials/empty_state.php';
                                    ?>
                                </td>
                            </tr>
                        <?php elseif (empty($linhas)): ?>
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <?php 
                                        $emptyMessage = "Nenhuma movimentação encontrada";
                                        $emptyDescription = "Nenhuma compra ou recarga registrada para este aluno no período especificado.";
                                        require __DIR__ . '/../partials/empty_state.php';
                                    ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- Linha Inicial de Saldo -->
                            <tr style="background-color: #f8fafc; font-weight: 600;">
                                <td class="cell-center font-tabular text-muted">-</td>
                                <td class="cell-left"><em>Saldo inicial</em></td>
                                <td class="cell-center text-muted">-</td>
                                <td class="cell-right text-muted">-</td>
                                <td class="cell-right text-muted">-</td>
                                <td class="cell-center text-muted">-</td>
                                <td class="cell-right font-tabular text-dark">
                                    R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <?php foreach ($linhas as $l): ?>
                                <?php 
                                    $isRec = ($l['tipo'] === 'recarga');
                                    $bgClass = $isRec ? 'style="background-color: #f0fdf4;"' : '';
                                ?>
                                <tr <?php echo $bgClass; ?>>
                                    <td class="cell-center font-tabular">
                                        <?php echo htmlspecialchars($l['data_formatada'] ?? date('d/m/Y', strtotime($l['data']))); ?>
                                    </td>
                                    <td class="cell-left">
                                        <strong><?php echo htmlspecialchars($l['descricao']); ?></strong>
                                    </td>
                                    <td class="cell-center font-tabular">
                                        <?php echo htmlspecialchars((string)$l['quantidade']); ?>
                                    </td>
                                    <td class="cell-right font-tabular">
                                        <?php 
                                            if (is_numeric($l['valor_unitario'])) {
                                                echo 'R$ ' . number_format((float)$l['valor_unitario'], 2, ',', '.');
                                            } else {
                                                echo htmlspecialchars((string)$l['valor_unitario']);
                                            }
                                        ?>
                                    </td>
                                    <td class="cell-right font-tabular <?php echo $isRec ? 'text-success font-weight-bold' : ''; ?>">
                                        R$ <?php echo number_format((float)$l['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="cell-center">
                                        <span class="code-tag font-tabular" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($l['forma_pagamento']); ?>
                                        </span>
                                    </td>
                                    <td class="cell-right font-tabular font-weight-bold <?php echo ($l['saldo'] < 0) ? 'text-danger' : 'text-dark'; ?>">
                                        R$ <?php echo number_format((float)$l['saldo'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($jaFiltrado) && !empty($linhas)): ?>
                        <tfoot>
                            <tr style="background-color: #f8fafc; font-weight: 800;">
                                <td colspan="4" class="cell-left">Total gasto no período</td>
                                <td class="cell-right font-tabular text-danger">
                                    R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?>
                                </td>
                                <td colspan="2" class="cell-right font-tabular text-primary">
                                    Saldo Final: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function marcarFiltroAlterado() {
    const fg = document.getElementById('filtroGeral');
    if (fg) fg.value = '0';
}

function definirPeriodo(tipo) {
    const hoje = new Date();
    const dataIni = document.getElementById('dataInicio');
    const dataFim = document.getElementById('dataFim');
    const fg = document.getElementById('filtroGeral');
    if (!dataIni || !dataFim) return;

    function formatar(d) {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    }

    if (tipo === 'todos') {
        dataIni.value = '';
        dataFim.value = '';
        if (fg) fg.value = '1';
        return;
    }

    if (fg) fg.value = '0';

    if (tipo === 'hoje') {
        dataIni.value = formatar(hoje);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'semana') {
        const diaSemana = hoje.getDay();
        const diffSegunda = (diaSemana === 0 ? -6 : 1) - diaSemana;
        const segunda = new Date(hoje);
        segunda.setDate(hoje.getDate() + diffSegunda);
        dataIni.value = formatar(segunda);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_atual') {
        const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        dataIni.value = formatar(primeiroDia);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_anterior') {
        const primeiroDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        const ultimoDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
        dataIni.value = formatar(primeiroDiaAnt);
        dataFim.value = formatar(ultimoDiaAnt);
    }
}
</script>
