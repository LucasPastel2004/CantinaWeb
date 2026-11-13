<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador de Gestão de Estoque, Entradas de Mercadorias e Registro de Perdas
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Configuracao;
use App\Models\Estoque;
use App\Models\Produto;
use App\Models\Categoria;

class EstoqueController extends Controller
{
    /**
     * Tela 6.1: Painel Geral de Saldos e Controle de Estoque
     */
    public function index(): void
    {
        $categoriaId = (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') ? (int)$_GET['categoria_id'] : null;
        $tipo        = (isset($_GET['tipo']) && $_GET['tipo'] !== '') ? trim($_GET['tipo']) : null;
        $statusPausa = (isset($_GET['status_pausa']) && $_GET['status_pausa'] !== '') ? trim($_GET['status_pausa']) : null;
        $apenasAlerta= isset($_GET['alerta']) && $_GET['alerta'] === '1';
        $busca       = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $filtrar     = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));

        // Carga sob demanda: ativa ao clicar em 'Filtrar' (mesmo com padrões) ou ao interagir com paginação/filtros
        $jaFiltrado  = $filtrar || !empty($busca) || $categoriaId !== null || !empty($tipo) || $apenasAlerta || !empty($statusPausa) || isset($_GET['pagina']);

        $filtros = [
            'categoria_id' => $categoriaId,
            'tipo'         => $tipo,
            'status_pausa' => $statusPausa,
            'apenas_alerta'=> $apenasAlerta,
            'busca'        => $busca
        ];

        $paginaAtual    = max(1, (int)($_GET['pagina'] ?? 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        if ($jaFiltrado) {
            $totalProdutos  = Estoque::contarSaldos($filtros);
            $totalPaginas   = max(1, (int)ceil($totalProdutos / $itensPorPagina));
            if ($paginaAtual > $totalPaginas) {
                $paginaAtual = $totalPaginas;
            }
            $offset = ($paginaAtual - 1) * $itensPorPagina;
            $produtos = Estoque::listarSaldos($filtros, $itensPorPagina, $offset);
        } else {
            $totalProdutos  = 0;
            $totalPaginas   = 1;
            $produtos       = [];
        }

        $metricas   = Estoque::obterMetricasGerais();
        $categorias = Categoria::listarTodas();

        $this->render('estoque/index', [
            'produtos'       => $produtos,
            'metricas'       => $metricas,
            'categorias'     => $categorias,
            'filtros'        => $filtros,
            'jaFiltrado'     => $jaFiltrado,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalProdutos,
            'itensPorPagina' => $itensPorPagina,
            'activeMenu'     => 'estoque',
            'title'          => 'Controle de Estoque & Saldos'
        ], 'admin');
    }

    /**
     * Tela 6.3: Lançamento Manual de Perdas de Mercadorias (RN-15)
     */
    public function perdas(): void
    {
        $produtos     = Produto::listarTodos();
        $ultimasPerdas= Estoque::listarPerdas(['limite' => 20]);
        $produtoPreSel= isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;

        $this->render('estoque/perdas', [
            'produtos'      => $produtos,
            'ultimasPerdas' => $ultimasPerdas,
            'produtoPreSel' => $produtoPreSel,
            'motivosPerda'  => Estoque::MOTIVOS_PERDA,
            'activeMenu'    => 'perdas',
            'title'         => 'Registro de Perdas de Mercadorias'
        ], 'admin');
    }

    /**
     * Processa a gravação da perda de estoque com validações e auditoria
     */
    public function perdasSalvar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/perdas')) {
            return;
        }

        $produtoId     = (int)($_POST['produto_id'] ?? 0);
        $quantidade    = (int)($_POST['quantidade'] ?? 0);
        $motivoPerda   = trim($_POST['motivo_perda'] ?? '');
        $justificativa = trim($_POST['justificativa'] ?? '');

        if ($produtoId <= 0) {
            Session::setFlash('danger', 'Selecione o produto que sofreu a perda.');
            $this->redirect('/estoque/perdas');
            return;
        }

        if ($quantidade <= 0) {
            Session::setFlash('danger', 'A quantidade da perda deve ser maior que zero.');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        if (!array_key_exists($motivoPerda, Estoque::MOTIVOS_PERDA)) {
            Session::setFlash('danger', 'Selecione um motivo de perda padronizado válido.');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        if (mb_strlen($justificativa, 'UTF-8') < 5) {
            Session::setFlash('danger', 'A justificativa é obrigatória para fins de auditoria (mínimo 5 caracteres).');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        $usuario = Auth::user();
        $operadorId = (int)($usuario['id'] ?? 1);

        try {
            Estoque::registrarPerda($produtoId, $quantidade, $motivoPerda, $justificativa, $operadorId);

            $produto = Produto::buscarPorId($produtoId);
            $nomeProd = $produto['descricao'] ?? 'Produto';
            $motivoNome = Estoque::MOTIVOS_PERDA[$motivoPerda];

            Session::setFlash('success', "Baixa de {$quantidade} un de '{$nomeProd}' registrada com sucesso por motivo de {$motivoNome}.");
            $this->redirect('/estoque/perdas');
        } catch (\DomainException $de) {
            Session::setFlash('danger', $de->getMessage());
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro inesperado ao registrar a perda: ' . $e->getMessage());
            $this->redirect('/estoque/perdas');
        }
    }

    /**
     * Atualização rápida do ponto de estoque mínimo
     */
    public function atualizarEstoqueMinimo(): void
    {
        if (!$this->validateCsrf(null, '/estoque')) {
            return;
        }

        $produtoId   = (int)($_POST['produto_id'] ?? 0);
        $novoMinimo  = (int)($_POST['estoque_minimo'] ?? 0);
        $usuario     = Auth::user();
        $usuarioId   = (int)($usuario['id'] ?? 1);

        if ($produtoId <= 0 || $novoMinimo < 0) {
            if ($this->isAjax()) {
                $this->json(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos.'], 400);
                return;
            }
            Session::setFlash('danger', 'Parâmetros inválidos para atualização do estoque mínimo.');
            $this->redirect('/estoque');
            return;
        }

        try {
            Estoque::atualizarEstoqueMinimo($produtoId, $novoMinimo, $usuarioId);

            if ($this->isAjax()) {
                $this->json(['sucesso' => true, 'mensagem' => 'Estoque mínimo atualizado com sucesso.']);
                return;
            }

            Session::setFlash('success', 'Ponto de estoque mínimo atualizado.');
            $this->redirect('/estoque');
        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['sucesso' => false, 'mensagem' => $e->getMessage()], 500);
                return;
            }
            Session::setFlash('danger', $e->getMessage());
            $this->redirect('/estoque');
        }
    }

    /**
     * Tela: Entrada Manual de Estoque (Substitui o fluxo XML de NF-e)
     */
    public function entrada(): void
    {
        $produtos         = Produto::listarTodos();
        $categorias       = Categoria::listarTodas();
        $ultimasEntradas  = Estoque::listarEntradasRecentes(20);
        $fornecedores     = Estoque::listarFornecedoresSugestao();
        $mapaFornecedores = Estoque::obterMapaProdutosFornecedores();
        $produtoPreSel    = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;

        $this->render('estoque/entrada', [
            'produtos'         => $produtos,
            'categorias'       => $categorias,
            'ultimasEntradas'  => $ultimasEntradas,
            'fornecedores'     => $fornecedores,
            'mapaFornecedores' => $mapaFornecedores,
            'produtoPreSel'    => $produtoPreSel,
            'activeMenu'       => 'entrada_estoque',
            'pageTitle'        => 'Entrada de Mercadorias',
            'title'            => 'Entrada de Mercadorias'
        ], 'admin');
    }

    /**
     * Processa a gravação da entrada manual de estoque (suporta lote ou item individual)
     */
    public function entradaSalvar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/entrada')) {
            return;
        }

        $fornecedor       = trim($_POST['fornecedor'] ?? '');
        $numeroDocumento  = trim($_POST['numero_documento'] ?? '');
        $observacoes      = trim($_POST['observacoes'] ?? '');
        $itensJson        = $_POST['itens_json'] ?? '';

        $itens = [];
        if (!empty($itensJson)) {
            $decoded = json_decode($itensJson, true);
            if (is_array($decoded)) {
                $itens = $decoded;
            }
        } elseif (!empty($_POST['itens']) && is_array($_POST['itens'])) {
            $itens = $_POST['itens'];
        } elseif (!empty($_POST['produto_id']) && !empty($_POST['quantidade'])) {
            // Suporte retrocompatível a envio de formulário com produto único
            $precoCusto = !empty($_POST['preco_custo']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custo']) : null;
            $itens[] = [
                'produto_id'  => (int)$_POST['produto_id'],
                'quantidade'  => (int)$_POST['quantidade'],
                'preco_custo' => $precoCusto
            ];
        }

        if (mb_strlen($fornecedor, 'UTF-8') < 2) {
            Session::setFlash('danger', 'Informe o nome do fornecedor (mínimo 2 caracteres).');
            $this->redirect('/estoque/entrada');
            return;
        }

        if (empty($itens)) {
            Session::setFlash('danger', 'Nenhum produto foi adicionado à lista de entrada. Adicione ao menos um item antes de gravar.');
            $this->redirect('/estoque/entrada');
            return;
        }

        $usuario = Auth::user();
        $operadorId = (int)($usuario['id'] ?? 1);

        try {
            $res = Estoque::darEntradaLoteManual($itens, $fornecedor, $numeroDocumento, $observacoes, $operadorId);

            $msg = "Entrada em estoque registrada com sucesso! Foram adicionadas {$res['total_unidades']} unidades em {$res['total_produtos']} produto(s) (Fornecedor: {$fornecedor}).";
            Session::setFlash('success', $msg);
            $this->redirect('/estoque/entrada');
        } catch (\DomainException $de) {
            Session::setFlash('danger', $de->getMessage());
            $this->redirect('/estoque/entrada');
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro inesperado ao registrar a entrada em lote: ' . $e->getMessage());
            $this->redirect('/estoque/entrada');
        }
    }
}
