<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão Ativa de Restrições Alimentares dos Filhos
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl           = $baseUrl ?? '';
$dependentes       = $dependentes ?? [];
$alunoSelecionado  = $alunoSelecionado ?? [];
$catalogoAlergenos = $catalogoAlergenos ?? [];
$alergenosDoAluno  = $alergenosDoAluno ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-microcopy">
                <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>/portal">Portal da Família</a></li>
                <li class="breadcrumb-item active" aria-current="page">Restrições Alimentares</li>
            </ol>
        </nav>
        <h1 class="h5 font-weight-bold text-dark mb-0">Restrições Alimentares e Alergias</h1>
    </div>
    <div>
        <a href="<?php echo $baseUrl; ?>/portal" class="btn btn-enterprise btn-secondary btn-sm">
            &larr; Voltar ao Início
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Seletor de Dependente (Caso haja mais de um) -->
    <div class="col-12 col-md-4">
        <div class="card-enterprise mb-3">
            <div class="card-enterprise-header">
                <h2 class="h6 font-weight-bold text-dark mb-0">Seus Dependentes</h2>
            </div>
            <div class="card-enterprise-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($dependentes as $dep): ?>
                        <?php $isSel = ((int)$dep['id'] === (int)$alunoSelecionado['id']); ?>
                        <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 <?php echo $isSel ? 'bg-light border-start border-primary border-3' : ''; ?>">
                            <div>
                                <div class="font-weight-semibold <?php echo $isSel ? 'text-primary' : 'text-dark'; ?>">
                                    <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-microcopy text-muted font-tabular">
                                    Turma: <?php echo htmlspecialchars($dep['nome_turma'] ?? 'Turma', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                    Matrícula: <?php echo htmlspecialchars($dep['matricula'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <?php if ($isSel): ?>
                                <span class="badge bg-primary text-white" style="font-size: 11px;">Selecionado</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Alérgenos do Dependente Selecionado -->
    <div class="col-12 col-md-8">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">
                        Alérgenos de: <?php echo htmlspecialchars($alunoSelecionado['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <span class="text-microcopy text-muted">
                        Turma: <?php echo htmlspecialchars($alunoSelecionado['nome_turma'] ?? '', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                        Turno: <?php echo ucfirst($alunoSelecionado['turno'] ?? 'matutino'); ?>
                    </span>
                </div>
                <div>
                    <span class="status-indicator <?php echo !empty($alergenosDoAluno) ? 'status-warning' : 'status-active'; ?>">
                        <span class="bullet">●</span> 
                        <?php echo count($alergenosDoAluno); ?> alérgeno(s) ativo(s)
                    </span>
                </div>
            </div>
            <div class="card-enterprise-body p-4">
                <form action="<?php echo $baseUrl; ?>/portal/alergias/salvar" method="POST">
                    <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                    <input type="hidden" name="aluno_id" value="<?php echo $alunoSelecionado['id']; ?>">

                    <div class="row g-3 mb-4">
                        <?php foreach ($catalogoAlergenos as $alerg): ?>
                            <?php 
                            $marcado = in_array((int)$alerg['id'], $alergenosDoAluno, true);
                            ?>
                            <div class="col-12 col-sm-6">
                                <label class="p-3 border rounded d-flex align-items-start gap-3 h-100 cursor-pointer transition-all" style="background-color: <?php echo $marcado ? '#fffbeb' : '#ffffff'; ?>; border-color: <?php echo $marcado ? '#fde68a' : '#e2e8f0'; ?> !important;" for="alerg_<?php echo $alerg['id']; ?>">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="alergenos[]" value="<?php echo $alerg['id']; ?>" id="alerg_<?php echo $alerg['id']; ?>" <?php echo $marcado ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-semibold text-dark mb-1">
                                            <?php echo htmlspecialchars($alerg['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="text-microcopy text-muted" style="font-size: 12px; line-height: 1.4;">
                                            <?php echo htmlspecialchars($alerg['descricao'] ?? 'Alergia ou intolerância alimentar diagnosticada.', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-microcopy text-muted">
                            As alterações entram em vigor imediatamente para os próximos agendamentos.
                        </span>
                        <button type="submit" class="btn btn-enterprise btn-primary">
                            Salvar Restrições Alimentares
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
