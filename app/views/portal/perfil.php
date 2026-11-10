<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Perfil e Dados Cadastrais do Responsável Familiar
 * 
 * Conforme docs/DESIGN.md e diretrizes de manutenção
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$responsavel = $responsavel ?? [];
$dependentes = $dependentes ?? [];

$cpfLimpo = preg_replace('/\D/', '', $responsavel['cpf'] ?? '');
$cpfFormatado = (strlen($cpfLimpo) === 11)
    ? substr($cpfLimpo, 0, 3) . '.' . substr($cpfLimpo, 3, 3) . '.' . substr($cpfLimpo, 6, 3) . '-' . substr($cpfLimpo, 9, 2)
    : ($responsavel['cpf'] ?? '—');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Meus Dados Cadastrais</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/portal" class="btn btn-enterprise btn-primary">
            &larr; Voltar ao Início
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Coluna 1: Titular da Conta Familiar & Segurança de Acesso -->
    <div class="col-12 col-lg-6">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <?php
                $isCoRespPerfil = !empty($responsavel['titular_id']) || (($responsavel['parentesco'] ?? '') === 'co_responsavel');
                $tituloCardPerfil = $isCoRespPerfil ? 'Dados do Co-responsável' : 'Titular da Conta Familiar';
                ?>
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary"><?php echo $tituloCardPerfil; ?></span>
                <div class="d-flex align-items-center gap-2">
                    <span class="status-indicator status-success"><span class="bullet">●</span> Ativo</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btn-toggle-editar" title="Editar dados cadastrais">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1" aria-hidden="true">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Editar Dados
                    </button>
                </div>
            </div>
            <div class="card-enterprise-body">
                <!-- ========================================== -->
                <!-- 1. MODO DE VISUALIZAÇÃO DOS DADOS CADASTRAIS -->
                <!-- ========================================== -->
                <div id="dados-view-mode">
                    <div class="mb-3">
                        <span class="text-microcopy text-muted d-block">Nome Completo</span>
                        <strong class="text-dark" style="font-size: 15px;"><?php echo htmlspecialchars($responsavel['nome'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <span class="text-microcopy text-muted d-block">CPF (Login)</span>
                            <span class="font-tabular text-dark fw-semibold"><?php echo htmlspecialchars($cpfFormatado, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-microcopy text-muted d-block">Telefone / Contato</span>
                            <span class="font-tabular text-dark fw-semibold">
                                <?php if (!empty($responsavel['whatsapp'])): ?>
                                    <?php echo htmlspecialchars($responsavel['whatsapp'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Não informado</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-microcopy text-muted d-block">E-mail de Contato</span>
                        <span class="text-dark">
                            <?php if (!empty($responsavel['email'])): ?>
                                <?php echo htmlspecialchars($responsavel['email'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Não informado</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="p-3 bg-light rounded-2 border">
                        <span class="text-microcopy text-muted d-block mb-1">Adesão aos Termos de Uso</span>
                        <div class="text-microcopy text-secondary">
                            <?php if (!empty($responsavel['termos_aceitos_em'])): ?>
                                <span class="text-success fw-semibold">&check; Termos e Políticas aceitos em <?php echo date('d/m/Y \à\s H:i', strtotime($responsavel['termos_aceitos_em'])); ?>.</span>
                            <?php else: ?>
                                <span class="text-muted">Termos ainda não formalizados.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php 
                    $outrosMembros = array_filter($membrosFamilia ?? [], fn($m) => (int)$m['id'] !== (int)($responsavel['id'] ?? 0));
                    if (!empty($outrosMembros)): 
                    ?>
                        <div class="mt-3 p-3 bg-light rounded-2 border">
                            <span class="text-microcopy text-muted d-block mb-2 font-weight-bold">Conta Familiar Compartilhada (Pai & Mãe)</span>
                            <div class="text-microcopy text-dark">
                                <?php foreach ($outrosMembros as $om): ?>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <div>
                                            <strong class="d-block text-dark"><?php echo htmlspecialchars($om['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <span class="text-muted font-tabular">CPF: <?php 
                                                $cpfOm = preg_replace('/\D/', '', $om['cpf']);
                                                echo substr($cpfOm, 0, 3) . '.***.***-' . substr($cpfOm, -2);
                                            ?></span>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary border font-tabular">
                                            <?php echo empty($om['titular_id']) ? 'Titular' : 'Co-responsável'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-microcopy text-muted d-block mt-2">
                                Os responsáveis vinculados compartilham a carteira familiar, o saldo e os mesmos dependentes.
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ========================================== -->
                <!-- 2. MODO DE EDIÇÃO DOS DADOS CADASTRAIS (CRUD) -->
                <!-- ========================================== -->
                <div id="dados-edit-mode" class="d-none">
                    <form action="<?php echo $baseUrl; ?>/portal/perfil/atualizar" method="POST" id="form-atualizar-dados">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">
                            <label for="perfil_nome" class="form-label text-microcopy text-muted mb-1">
                                Nome Completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="perfil_nome" 
                                   name="nome" 
                                   value="<?php echo htmlspecialchars($responsavel['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                   required 
                                   maxlength="100"
                                   autocomplete="name">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label text-microcopy text-muted mb-1">
                                    CPF (Login)
                                </label>
                                <input type="text" 
                                       class="form-control form-control-sm bg-light text-muted font-tabular" 
                                       value="<?php echo htmlspecialchars($cpfFormatado, ENT_QUOTES, 'UTF-8'); ?>" 
                                       readonly 
                                       disabled>
                                <small class="text-microcopy text-muted d-block mt-1">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1" aria-hidden="true">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    O CPF não pode ser trocado.
                                </small>
                            </div>
                            <div class="col-6">
                                <label for="perfil_whatsapp" class="form-label text-microcopy text-muted mb-1">
                                    Telefone / Contato
                                </label>
                                <input type="text" 
                                       class="form-control form-control-sm input-phone font-tabular" 
                                       id="perfil_whatsapp" 
                                       name="whatsapp" 
                                       value="<?php echo htmlspecialchars($responsavel['whatsapp'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                       placeholder="(00) 00000-0000" 
                                       maxlength="20"
                                       autocomplete="tel">
                                <small class="text-microcopy text-muted d-block mt-1">
                                    Preencha, altere ou apague para remover.
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="perfil_email" class="form-label text-microcopy text-muted mb-1">
                                E-mail de Contato
                            </label>
                            <input type="email" 
                                   class="form-control form-control-sm" 
                                   id="perfil_email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($responsavel['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" 
                                   placeholder="exemplo@email.com" 
                                   maxlength="100"
                                   autocomplete="email">
                            <small class="text-microcopy text-muted d-block mt-1">
                                Preencha, altere ou apague para remover. Utilizado para recuperação de senha.
                            </small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btn-cancelar-edicao">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ========================================== -->
                <!-- 3. SEGURANÇA DE ACESSO — ALTERAÇÃO DE SENHA -->
                <!-- ========================================== -->
                <hr class="my-4" style="border-color: var(--border-default, #e2e8f0);">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Segurança de Acesso</span>
                    <span class="text-microcopy text-muted">Alteração de Senha</span>
                </div>

                <form action="<?php echo $baseUrl; ?>/portal/perfil/senha" method="POST" id="form-alterar-senha">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="senha_atual" class="form-label text-microcopy text-muted mb-1">
                            Senha Atual <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="senha_atual" 
                               name="senha_atual" 
                               required 
                               autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label for="nova_senha" class="form-label text-microcopy text-muted mb-1">
                            Nova Senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="nova_senha" 
                               name="nova_senha" 
                               required 
                               minlength="6" 
                               autocomplete="new-password">
                        <small class="text-microcopy text-muted">Use no mínimo 6 caracteres alfanuméricos.</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label text-microcopy text-muted mb-1">
                            Confirmar Nova Senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="confirmar_senha" 
                               name="confirmar_senha" 
                               required 
                               minlength="6" 
                               autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-microcopy text-muted">
                            A alteração entra em vigor imediatamente.
                        </span>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
                            Atualizar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Coluna 2: Dependentes Vinculados & Informações sobre Acesso -->
    <div class="col-12 col-lg-6 d-flex flex-column gap-4">
        <!-- Dependentes Vinculados -->
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Dependentes Vinculados</span>
                <span class="text-microcopy text-muted font-tabular"><?php echo count($dependentes); ?> aluno(s)</span>
            </div>
            <div class="card-enterprise-body">
                <?php if (empty($dependentes)): ?>
                    <div class="text-center py-4 text-muted text-microcopy">
                        <p class="mb-0">Nenhum dependente vinculado no momento.</p>
                        <small>A vinculação de alunos é realizada pela secretaria escolar.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dependentes as $dep): ?>
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="text-microcopy text-muted font-tabular">
                                        Matrícula: <?php echo htmlspecialchars($dep['matricula'], ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turma: <?php echo htmlspecialchars($dep['nome_turma'] ?? 'A definir', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turno: <?php echo ucfirst($dep['turno'] ?? 'matutino'); ?>
                                    </div>
                                </div>
                                <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm">
                                    Restrições
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 p-3 bg-light rounded-2 border">
                    <span class="text-microcopy text-dark d-block mb-1 fw-bold">Secretaria Escolar</span>
                    <p class="text-microcopy text-secondary mb-0">
                        Para inclusão de novos dependentes ou transferência de turma, favor procurar a secretaria do Colégio Monteiro Lobato.
                    </p>
                </div>
            </div>
        </div>

        <!-- Informações sobre Políticas de Acesso -->
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Como Funciona o Acesso</span>
                <span class="status-indicator status-active"><span class="bullet">●</span> Protegido</span>
            </div>
            <div class="card-enterprise-body">
                <div class="mb-3">
                    <strong class="text-dark d-block mb-1" style="font-size: 14px;">Autenticação e Senha</strong>
                    <p class="text-secondary text-microcopy mb-0">
                        O acesso ao Portal da Família é individual por responsável legal, autenticado pelo CPF e senha criptografada em padrão seguro.
                    </p>
                </div>

                <div class="p-3 bg-light rounded-2 border mb-3">
                    <span class="text-microcopy text-dark d-block mb-1 fw-bold">Esqueceu sua senha?</span>
                    <p class="text-microcopy text-secondary mb-0">
                        Caso esqueça sua senha de acesso, na tela inicial de login clique em <strong>"Esqueci minha senha"</strong>. As orientações seguras de redefinição serão despachadas para o seu e-mail cadastrado.
                    </p>
                </div>

                <div class="p-3 bg-light rounded-2 border">
                    <span class="text-microcopy text-dark d-block mb-1 fw-bold">Dicas de Segurança</span>
                    <ul class="text-secondary text-microcopy mb-0 ps-3">
                        <li>Não forneça sua senha ou códigos recebidos por e-mail para outras pessoas.</li>
                        <li>Ao utilizar computadores compartilhados, lembre-se de clicar em <strong>"Sair"</strong> ao concluir seus pedidos.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const viewMode = document.getElementById('dados-view-mode');
    const editMode = document.getElementById('dados-edit-mode');
    const btnEditar = document.getElementById('btn-toggle-editar');
    const btnCancelar = document.getElementById('btn-cancelar-edicao');

    function exibirModoEdicao() {
        if (viewMode && editMode) {
            viewMode.classList.add('d-none');
            editMode.classList.remove('d-none');
            if (btnEditar) btnEditar.classList.add('d-none');
            const inputNome = document.getElementById('perfil_nome');
            if (inputNome) inputNome.focus();
        }
    }

    function exibirModoVisualizacao() {
        if (viewMode && editMode) {
            editMode.classList.add('d-none');
            viewMode.classList.remove('d-none');
            if (btnEditar) btnEditar.classList.remove('d-none');
        }
    }

    if (btnEditar) {
        btnEditar.addEventListener('click', exibirModoEdicao);
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', exibirModoVisualizacao);
    }

    // Se houver hash #editar na URL (ex: redirecionamento após validação), abre direto em edição
    if (window.location.hash === '#editar') {
        exibirModoEdicao();
    }
});
</script>
