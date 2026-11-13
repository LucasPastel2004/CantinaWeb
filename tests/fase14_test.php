<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 14: Gestão de Estoque Manual, Lotes, Perdas e Alertas
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Estoque.php';
require_once __DIR__ . '/../app/models/Auditoria.php';

use App\Models\Produto;
use App\Models\Estoque;

class Fase14Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function executar(): void
    {
        echo "\n=======================================================\n";
        echo "INICIANDO TESTES DA FASE 14 — ESTOQUE MANUAL E PERDAS\n";
        echo "=======================================================\n\n";

        $this->testarRemocaoModuloXmlLegado();
        $this->testarEntradaManualELoteAtomico();
        $this->testarLancamentoPerdasERegrasNegocio();
        $this->testarSaldosEAlertaEstoqueMinimo();
        $this->testarExtratoMovimentacoes();

        echo "\n-------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas.\n";
        if ($this->assercoesSucesso === $this->totalAssercoes) {
            echo "STATUS: SUCESSO TOTAL (100% DE APROVAÇÃO)\n";
        } else {
            echo "STATUS: FALHA DETECTADA\n";
            exit(1);
        }
        echo "=======================================================\n\n";
    }

    /**
     * 1. Garante remoção completa do módulo legado de importação XML NF-e
     */
    private function testarRemocaoModuloXmlLegado(): void
    {
        echo "1. Verificando remoção do módulo legado de XML NF-e...\n";

        $this->assert(!file_exists(__DIR__ . '/../app/services/XmlParserService.php'), "XmlParserService.php removido");
        $this->assert(!file_exists(__DIR__ . '/../app/models/NotaFiscal.php'), "Model NotaFiscal.php removido");
        $this->assert(!file_exists(__DIR__ . '/../app/models/ItemNotaFiscal.php'), "Model ItemNotaFiscal.php removido");
        $this->assert(!method_exists(Estoque::class, 'darEntradaXml'), "Método Estoque::darEntradaXml() removido");
        $this->assert(!file_exists(__DIR__ . '/../app/views/estoque/conferir_xml.php'), "View conferir_xml.php removida");
        $this->assert(!file_exists(__DIR__ . '/../app/views/estoque/notas_fiscais.php'), "View notas_fiscais.php removida");
        $this->assert(!file_exists(__DIR__ . '/../app/views/estoque/nota_fiscal_detalhes.php'), "View nota_fiscal_detalhes.php removida");
    }

    /**
     * 2. Teste de entrada manual unitária e entrada manual em lote atômica
     */
    private function testarEntradaManualELoteAtomico(): void
    {
        echo "\n2. Testando entrada manual unitária e em lote no estoque...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        $codProd1 = 'TEST-IND-LOTE1-' . bin2hex(random_bytes(3));
        $prodId1 = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProd1,
            'descricao'      => 'Achocolatado 200ml Teste Lote 1',
            'preco_venda'    => 4.50,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 5,
            'estoque_minimo' => 5
        ], [], $adminId);

        $codProd2 = 'TEST-IND-LOTE2-' . bin2hex(random_bytes(3));
        $prodId2 = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProd2,
            'descricao'      => 'Biscoito Integral Teste Lote 2',
            'preco_venda'    => 3.80,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 10,
            'estoque_minimo' => 4
        ], [], $adminId);

        // Entrada manual unitária
        $okUnitaria = Estoque::darEntradaManual($prodId1, 15, 'Distribuidora Láctea Teste', 2.40, 'Reposição unitária F14', $adminId);
        $this->assert($okUnitaria === true, "Estoque::darEntradaManual executada com sucesso");
        $p1 = Produto::buscarPorId($prodId1);
        $this->assert((int)$p1['estoque_atual'] === 20, "Saldo atualizado para 20 un após entrada manual (5 + 15)");
        $this->assert((float)$p1['preco_custo'] === 2.40, "Preço de custo atualizado para R$ 2,40");

        // Entrada em lote atômica com dois itens
        $resumoLote = Estoque::darEntradaLoteManual([
            ['produto_id' => $prodId1, 'quantidade' => 10, 'preco_custo' => 2.50],
            ['produto_id' => $prodId2, 'quantidade' => 20, 'preco_custo' => 1.90],
        ], 'Atacadão Escolar Teste', 'REC-1042', 'Compra semanal F14', $adminId);

        $this->assert((int)$resumoLote['total_produtos'] === 2, "Resumo de lote contabiliza 2 produtos processados");
        $this->assert((int)$resumoLote['total_unidades'] === 30, "Resumo de lote contabiliza 30 unidades adicionadas");

        $p1PosLote = Produto::buscarPorId($prodId1);
        $p2PosLote = Produto::buscarPorId($prodId2);
        $this->assert((int)$p1PosLote['estoque_atual'] === 30, "Produto 1 avançou para 30 un (20 + 10)");
        $this->assert((int)$p2PosLote['estoque_atual'] === 30, "Produto 2 avançou para 30 un (10 + 20)");

        // Atomicidade: se o segundo item falhar, o primeiro não pode ser incrementado
        $bloqueouLoteInvalido = false;
        try {
            Estoque::darEntradaLoteManual([
                ['produto_id' => $prodId1, 'quantidade' => 5],
                ['produto_id' => 999999999, 'quantidade' => 5],
            ], 'Fornecedor Rollback', null, null, $adminId);
        } catch (\DomainException $e) {
            $bloqueouLoteInvalido = true;
        }
        $this->assert($bloqueouLoteInvalido, "Entrada em lote rejeita produto inexistente com DomainException");
        $p1PosRollback = Produto::buscarPorId($prodId1);
        $this->assert((int)$p1PosRollback['estoque_atual'] === 30, "Rollback atômico preservou saldo anterior (30 un) do Produto 1");

        // Verifica registro em estoque_movimentacoes
        $stmtMov = $this->pdo->prepare("SELECT * FROM estoque_movimentacoes WHERE produto_id = :p AND tipo_movimentacao = 'entrada_fornecedor' ORDER BY id DESC LIMIT 1");
        $stmtMov->execute(['p' => $prodId2]);
        $mov = $stmtMov->fetch();
        $this->assert($mov !== false && (int)$mov['quantidade'] === 20, "Movimentação 'entrada_fornecedor' (20 un) gravada corretamente");
    }

    /**
     * 3. Testes de lançamento manual de perdas (RN-15) e regras de negócio
     */
    private function testarLancamentoPerdasERegrasNegocio(): void
    {
        echo "\n3. Testando lançamento de perdas e travas de integridade (RN-15)...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        $codProd = 'TEST-IND-PERDA-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProd,
            'descricao'      => 'Barra de Cereal Teste Perda',
            'preco_venda'    => 3.50,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 20,
            'estoque_minimo' => 5
        ], [], $adminId);

        $okPerda = Estoque::registrarPerda(
            $prodId,
            5,
            Estoque::MOTIVO_VENCIMENTO,
            'Lote com validade expirada na prateleira',
            $adminId
        );
        $this->assert($okPerda === true, "Perda válida (5 un, motivo vencimento) registrada com sucesso");

        $prodAposPerda = Produto::buscarPorId($prodId);
        $this->assert((int)$prodAposPerda['estoque_atual'] === 15, "Saldo físico reduzido de 20 para 15 unidades");

        $stmtAudit = $this->pdo->prepare("SELECT * FROM logs_auditoria WHERE acao = 'perda_estoque' AND registro_id = :prod_id ORDER BY id DESC LIMIT 1");
        $stmtAudit->execute(['prod_id' => $prodId]);
        $audit = $stmtAudit->fetch();

        $this->assert($audit !== false, "Trilha de auditoria gerada com ação 'perda_estoque'");
        $this->assert(str_contains($audit['justificativa'], 'Vencimento'), "Auditoria contém descrição do motivo da perda");

        $bloqueouExcesso = false;
        try {
            Estoque::registrarPerda($prodId, 50, Estoque::MOTIVO_AVARIA, 'Tentativa de saldo negativo', $adminId);
        } catch (\DomainException $e) {
            $bloqueouExcesso = true;
        }
        $this->assert($bloqueouExcesso, "Sistema bloqueia perda que exceda o saldo físico disponível");

        $bloqueouMotivoInvalido = false;
        try {
            Estoque::registrarPerda($prodId, 1, 'motivo_fantasma', 'Justificativa válida', $adminId);
        } catch (\DomainException $e) {
            $bloqueouMotivoInvalido = true;
        }
        $this->assert($bloqueouMotivoInvalido, "Sistema bloqueia motivo de perda fora dos 4 padronizados pelo FSD");

        $bloqueouJustificativaCurta = false;
        try {
            Estoque::registrarPerda($prodId, 1, Estoque::MOTIVO_AVARIA, 'abc', $adminId);
        } catch (\DomainException $e) {
            $bloqueouJustificativaCurta = true;
        }
        $this->assert($bloqueouJustificativaCurta, "Sistema exige justificativa com no mínimo 5 caracteres");
    }

    /**
     * 4. Teste de saldos analíticos e alerta de estoque mínimo
     */
    private function testarSaldosEAlertaEstoqueMinimo(): void
    {
        echo "\n4. Testando saldos analíticos e alerta de estoque mínimo...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        $codProd = 'TEST-IND-ALERTA-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'      => $catId,
            'codigo_interno'    => $codProd,
            'descricao'         => 'Refrigerante Orgânico Teste Alerta',
            'preco_venda'       => 7.00,
            'tipo'              => 'industrializado_continuo',
            'estoque_atual'     => 4,
            'estoque_reservado' => 0,
            'estoque_minimo'    => 5
        ], [], $adminId);

        $saldos = Estoque::listarSaldos(['busca' => $codProd]);
        $this->assert(count($saldos) === 1, "Estoque::listarSaldos localizou o produto de teste");
        $this->assert((int)$saldos[0]['em_alerta_minimo'] === 1, "Produto com estoque 4 e mínimo 5 é marcado como em_alerta_minimo = 1");

        Estoque::atualizarEstoqueMinimo($prodId, 2, $adminId);
        $saldosApos = Estoque::listarSaldos(['busca' => $codProd]);
        $this->assert((int)$saldosApos[0]['em_alerta_minimo'] === 0, "Após ajustar mínimo para 2, alerta é desativado (4 > 2)");

        $metricas = Estoque::obterMetricasGerais();
        $this->assert(isset($metricas['total_produtos']) && $metricas['total_produtos'] > 0, "obterMetricasGerais retorna total de produtos");
        $this->assert(isset($metricas['valor_estoque_venda']), "obterMetricasGerais calcula valor de estoque em venda");
    }

    /**
     * 5. Teste de extrato de movimentações e listagem de perdas
     */
    private function testarExtratoMovimentacoes(): void
    {
        echo "\n5. Testando extrato de movimentações e histórico de perdas...\n";

        $movs = Estoque::listarMovimentacoes(['limite' => 10]);
        $this->assert(is_array($movs), "Estoque::listarMovimentacoes retorna array de registros");
        $this->assert(!empty($movs), "Existem movimentações registradas na base");

        $entradas = Estoque::listarEntradasRecentes(5);
        $this->assert(is_array($entradas) && !empty($entradas), "Estoque::listarEntradasRecentes retorna entradas recentes");

        $perdas = Estoque::listarPerdas(['limite' => 10]);
        $this->assert(is_array($perdas), "Estoque::listarPerdas retorna array");
        $this->assert(!empty($perdas), "Histórico de perdas contém os registros criados nos testes");
        $this->assert($perdas[0]['tipo_movimentacao'] === 'saida_perda', "Primeiro item do histórico é do tipo 'saida_perda'");
    }
}

$teste = new Fase14Test();
$teste->executar();
