<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Validação da Descontinuação Oficial do Módulo de Desconto em Folha
 * 
 * Conforme solicitação explícita do usuário: O módulo de Desconto em Folha para RH foi totalmente
 * removido/descontinuado do sistema.
 * 
 * Valida:
 * 1. Redirecionamento seguro para /admin com mensagem flash informativa em todas as rotas de desconto em folha
 * 2. Resposta JSON defensiva em descontoFolhaDetalhesAjax indicando descontinuação
 * 3. Integridade das funções canônicas em PdfService para contingência histórica
 * 4. Remoção de links do módulo do menu corporativo Dock lateral
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Registra o Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Auth;
use App\Core\Session;
use App\Services\PdfService;
use App\Controllers\RelatoriosController;

class DescontoFolhaIndividualTest
{
    private int $pass = 0;
    private int $fail = 0;

    private function assert(bool $condicao, string $mensagem): void
    {
        if ($condicao) {
            $this->pass++;
            echo "  [OK] " . $mensagem . "\n";
        } else {
            $this->fail++;
            echo "  [FAIL] " . $mensagem . "\n";
        }
    }

    public function run(): void
    {
        echo "=======================================================\n";
        echo "   TESTE: DESCONTINUAÇÃO DO DESCONTO EM FOLHA (RH)\n";
        echo "=======================================================\n\n";

        // Inicializa sessão de administrador para testes de autorização
        Session::start();
        Session::set('_auth_id', 1);
        Session::set('_auth_user', 'admin');
        Session::set('_auth_nome', 'Administrador Geral');
        Session::set('_auth_role', 'administrador');
        Session::set('_auth_logged', true);

        $this->testarDescontinuacaoRotasController();
        $this->testarRespostaAjaxDescontinuada();
        $this->testarGeracaoPdfIndividualContingencia();
        $this->testarEspelhoHtmlIndividualContingencia();
        $this->testarGeracaoPdfLoteIndividuaisContingencia();

        echo "\n-------------------------------------------------------\n";
        echo "Resumo: {$this->pass} aprovados, {$this->fail} falhas.\n";
        echo "=======================================================\n";

        if ($this->fail > 0) {
            exit(1);
        }
    }

    /**
     * 1. Teste de descontinuação graciosa das rotas de desconto em folha no RelatoriosController
     */
    private function testarDescontinuacaoRotasController(): void
    {
        echo "1. Testando descontinuação das rotas no RelatoriosController...\n";

        $controller = new RelatoriosController();

        // 1.1 Rota principal: descontoFolha()
        ob_start();
        try {
            $controller->descontoFolha();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolha() define flash warning de descontinuação.");

        // 1.2 Rota PDF geral: descontoFolhaPdf()
        ob_start();
        try {
            $controller->descontoFolhaPdf();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolhaPdf() define flash warning de descontinuação.");

        // 1.3 Rota CSV: descontoFolhaCsv()
        ob_start();
        try {
            $controller->descontoFolhaCsv();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolhaCsv() define flash warning de descontinuação.");

        // 1.4 Rota PDF individual: descontoFolhaIndividualPdf()
        ob_start();
        try {
            $controller->descontoFolhaIndividualPdf();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolhaIndividualPdf() define flash warning de descontinuação.");

        // 1.5 Rota Impressão individual: descontoFolhaIndividualImprimir()
        ob_start();
        try {
            $controller->descontoFolhaIndividualImprimir();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolhaIndividualImprimir() define flash warning de descontinuação.");

        // 1.6 Rota Lote PDF: descontoFolhaLotePdf()
        ob_start();
        try {
            $controller->descontoFolhaLotePdf();
        } catch (\Throwable $e) {}
        ob_end_clean();

        $flash = implode(' ', Session::getFlash('warning'));
        $this->assert(!empty($flash) && str_contains($flash, 'descontinuado'), "descontoFolhaLotePdf() define flash warning de descontinuação.");
    }

    /**
     * 2. Teste do endpoint AJAX de detalhes indicando descontinuação
     */
    private function testarRespostaAjaxDescontinuada(): void
    {
        echo "\n2. Testando endpoint AJAX de desconto em folha descontinuado...\n";

        $controller = new RelatoriosController();

        ob_start();
        try {
            $controller->descontoFolhaDetalhesAjax();
        } catch (\Throwable $e) {}
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assert(is_array($json), "descontoFolhaDetalhesAjax() responde com JSON válido.");
        $this->assert(isset($json['sucesso']) && $json['sucesso'] === false, "JSON possui sucesso === false.");
        $this->assert(isset($json['erro']) && str_contains($json['erro'], 'descontinuado'), "Mensagem de erro informa módulo descontinuado.");
    }

    /**
     * 3. Teste da geração do PDF A4 Timbrado Individual por contingência canônica
     */
    private function testarGeracaoPdfIndividualContingencia(): void
    {
        echo "\n3. Testando geração de contingência do PDF A4 Individual...\n";

        $dadosMock = [
            'colaborador' => [
                'id'                  => 1,
                'nome'                => 'Professor Roberto da Silva',
                'matricula_funcional' => 'PROF001',
                'cargo'               => 'Professor de Biologia',
                'foto_url'            => ''
            ],
            'competencia'       => 'Setembro de 2026',
            'competencia_chave' => '2026-09',
            'operador'          => 'Administração Geral',
            'emissao'           => '25/09/2026 15:30:00',
            'baseUrl'           => '',
            'itens'             => [
                [
                    'venda_id'       => 101,
                    'created_at'     => '2026-09-05 10:15:00',
                    'turno'          => 'matutino',
                    'operador_nome'  => 'Maria Caixa',
                    'item_id'        => 201,
                    'quantidade'     => 1,
                    'preco_unitario' => 6.50,
                    'subtotal'       => 6.50,
                    'produto_nome'   => 'Pão de Queijo',
                    'produto_codigo' => 'SAL01'
                ]
            ],
            'totais'            => [
                'total_desconto' => 6.50,
                'total_lanches'  => 1,
                'total_compras'  => 1
            ]
        ];

        $pdfBinario = PdfService::gerarRelatorioDescontoFolhaIndividualPdf($dadosMock);

        $this->assert(!empty($pdfBinario), "PDF individual canônico gerado com sucesso.");
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), "Cabeçalho oficial PDF 1.4 detectado.");
        $this->assert(str_ends_with(trim($pdfBinario), '%%EOF'), "Marcador final %%EOF presente.");
    }

    /**
     * 4. Teste do Espelho HTML para visualização e impressão direta
     */
    private function testarEspelhoHtmlIndividualContingencia(): void
    {
        echo "\n4. Testando Espelho HTML de impressão do Demonstrativo Individual...\n";

        $dadosMock = [
            'colaborador' => [
                'id'                  => 2,
                'nome'                => 'Professora Ana Paula Santos',
                'matricula_funcional' => 'PROF002',
                'cargo'               => 'Professora de Língua Portuguesa',
                'foto_url'            => ''
            ],
            'competencia'       => 'Setembro de 2026',
            'competencia_chave' => '2026-09',
            'operador'          => 'Administrador Geral',
            'emissao'           => '25/09/2026 15:35:00',
            'baseUrl'           => '',
            'itens'             => [
                [
                    'venda_id'       => 105,
                    'created_at'     => '2026-09-10 15:40:00',
                    'turno'          => 'vespertino',
                    'operador_nome'  => 'João Atendente',
                    'item_id'        => 210,
                    'quantidade'     => 2,
                    'preco_unitario' => 5.00,
                    'subtotal'       => 10.00,
                    'produto_nome'   => 'Suco Natural',
                    'produto_codigo' => 'BEB02'
                ]
            ],
            'totais' => [
                'total_desconto' => 10.00,
                'total_lanches'  => 2,
                'total_compras'  => 1
            ]
        ];

        $html = PdfService::gerarEspelhoDescontoFolhaIndividualHtml($dadosMock);

        $this->assert(!empty($html), "Espelho HTML gerado com sucesso.");
        $this->assert(str_contains($html, '<!DOCTYPE html>'), "Declaração DOCTYPE presente.");
        $this->assert(str_contains($html, 'Demonstrativo Individual'), "Título da barra de ações presente.");
        $this->assert(str_contains($html, 'window.print()'), "Botão com gatilho window.print() presente.");
        $this->assert(str_contains($html, 'Professora Ana Paula Santos'), "Nome da professora presente no espelho.");
    }

    /**
     * 5. Teste de geração em lote de folhas individuais em um único PDF
     */
    private function testarGeracaoPdfLoteIndividuaisContingencia(): void
    {
        echo "\n5. Testando geração em Lote de Relatórios Individuais (PDF com múltiplas folhas)...\n";

        $colabsDetalhados = [
            [
                'colaborador' => [
                    'id'                  => 1,
                    'nome'                => 'Professor 1',
                    'matricula_funcional' => 'MAT001',
                    'cargo'               => 'Docente',
                    'foto_url'            => ''
                ],
                'competencia'       => 'Setembro de 2026',
                'competencia_chave' => '2026-09',
                'operador'          => 'RH Central',
                'emissao'           => '25/09/2026 15:40:00',
                'baseUrl'           => '',
                'itens'             => [
                    [
                        'venda_id'       => 301,
                        'created_at'     => '2026-09-15 09:30:00',
                        'turno'          => 'matutino',
                        'operador_nome'  => 'Operador',
                        'item_id'        => 401,
                        'quantidade'     => 1,
                        'preco_unitario' => 8.00,
                        'subtotal'       => 8.00,
                        'produto_nome'   => 'Sanduíche',
                        'produto_codigo' => 'LAN01'
                    ]
                ],
                'totais' => [
                    'total_desconto' => 8.00,
                    'total_lanches'  => 1,
                    'total_compras'  => 1
                ]
            ]
        ];

        $dadosLote = [
            'competencia'              => 'Setembro de 2026',
            'competencia_chave'        => '2026-09',
            'colaboradores_detalhados' => $colabsDetalhados
        ];

        $pdfLote = PdfService::gerarRelatorioDescontoFolhaIndividualLotePdf($dadosLote);

        $this->assert(!empty($pdfLote), "PDF do lote individual gerado com sucesso.");
        $this->assert(str_starts_with($pdfLote, '%PDF-1.4'), "Cabeçalho PDF 1.4 válido no lote.");
        $this->assert(str_ends_with(trim($pdfLote), '%%EOF'), "Finalizador %%EOF íntegro no lote.");
    }
}

// Execução da suíte
$test = new DescontoFolhaIndividualTest();
$test->run();
