<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Carga Sob Demanda na Consulta de Tickets (/operacional/tickets)
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// PSR-4 Autoloader
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

use App\Core\Session;
use App\Core\Auth;
use App\Controllers\TicketsController;
use App\Models\Ticket;

class TicketsCargaSobDemandaTest
{
    private int $passou = 0;
    private int $falhou = 0;
    private array $erros = [];

    private function assert(bool $condicao, string $mensagem): void
    {
        if ($condicao) {
            $this->passou++;
            echo "  [PASS] {$mensagem}\n";
        } else {
            $this->falhou++;
            $this->erros[] = $mensagem;
            echo "  [FAIL] {$mensagem}\n";
        }
    }

    public function executar(): void
    {
        echo "=================================================================\n";
        echo " TESTES: CARGA SOB DEMANDA NA CONSULTA RÁPIDA DE TICKETS\n";
        echo "=================================================================\n\n";

        // Simula login de operador
        Session::start();
        $_SESSION['usuario'] = [
            'id' => 1,
            'nome' => 'Operador Cantina',
            'perfil' => 'operador',
            'email' => 'operador@escola.com'
        ];
        Session::set('_auth_logged', true);
        Session::set('_auth_type', 'usuario');
        Session::set('_auth_id', 1);
        Session::set('_auth_nome', 'Operador Cantina');
        Session::set('_auth_role', 'operador');

        $dataTeste = '2026-10-30';
        $turnoTeste = 'vespertino';

        // --- 1. Acesso Inicial sem Filtro (Lista Deve Iniciar Vazia) ---
        echo "--- 1. Acesso Inicial sem Filtro ---\n";
        $_GET = [
            'data' => $dataTeste,
            'turno' => $turnoTeste
        ];
        $_POST = [];

        ob_start();
        $controller = new TicketsController();
        $controller->index();
        $htmlInicial = ob_get_clean();

        $this->assert(
            str_contains($htmlInicial, 'Consulta Rápida de Tickets &amp; Emissão de 2ª Via') || str_contains($htmlInicial, 'Consulta Rápida de Tickets'),
            "Cabeçalho da seção de Consulta Rápida de Tickets presente"
        );
        $this->assert(
            str_contains($htmlInicial, 'Não há nada para mostrar aqui'),
            "Estado vazio padrão presente na lista inicial de tickets"
        );
        $this->assert(
            str_contains($htmlInicial, 'Utilize os filtros acima'),
            "Mensagem instrutiva orientando a usar os filtros ou clicar em Buscar"
        );
        $this->assert(
            !str_contains($htmlInicial, 'title="Limpar Filtro"'),
            "Botão 'Limpar' NÃO aparece no acesso inicial"
        );
        $this->assert(
            !str_contains($htmlInicial, 'Reimprimir 2ª Via'),
            "Tabela de tickets NÃO renderizada no acesso inicial vazio"
        );

        // --- 2. Busca Geral sem Mexer nos Filtros (filtrar=1 com campos vazios) ---
        echo "\n--- 2. Busca Geral sem Mexer nos Filtros (filtrar=1) ---\n";
        $_GET = [
            'data' => $dataTeste,
            'turno' => $turnoTeste,
            'filtrar' => '1',
            'q' => '',
            'turma_id' => '',
            'status_ticket' => ''
        ];

        ob_start();
        $controller = new TicketsController();
        $controller->index();
        $htmlGeral = ob_get_clean();

        $this->assert(
            str_contains($htmlGeral, 'title="Limpar Filtro"'),
            "Botão 'Limpar' exibido após clicar em Buscar"
        );
        $this->assert(
            str_contains($htmlGeral, 'Reimprimir 2ª Via'),
            "Tabela de tickets carregada com sucesso na busca geral"
        );
        $this->assert(
            str_contains($htmlGeral, 'TCK-20261030') || str_contains($htmlGeral, 'ENZO CAMPOS') || str_contains($htmlGeral, 'MANUELLA'),
            "Tickets do turno encontrados e renderizados na grade geral"
        );
        $this->assert(
            str_contains($htmlGeral, 'ticket(s) localizado(s)'),
            "Badge indicativa do quantitativo de tickets localizados exibida"
        );

        // --- 3. Busca Específica por Nome / Termo ---
        echo "\n--- 3. Busca Específica por Nome (q=Enzo) ---\n";
        $_GET = [
            'data' => $dataTeste,
            'turno' => $turnoTeste,
            'filtrar' => '1',
            'q' => 'Enzo'
        ];

        ob_start();
        $controller = new TicketsController();
        $controller->index();
        $htmlBuscaNome = ob_get_clean();

        $this->assert(
            str_contains($htmlBuscaNome, 'ENZO CAMPOS'),
            "Aluno pesquisado 'Enzo' localizado na grade"
        );
        $this->assert(
            !str_contains($htmlBuscaNome, 'MANUELLA DUARTE') && !str_contains($htmlBuscaNome, 'JÚLIA MARTINS'),
            "Outros alunos devidamente filtrados da grade específica"
        );

        // --- 4. Busca por Termo Inexistente ---
        echo "\n--- 4. Busca por Termo Inexistente ---\n";
        $_GET = [
            'data' => $dataTeste,
            'turno' => $turnoTeste,
            'filtrar' => '1',
            'q' => 'NomeInexistenteXYZ123'
        ];

        ob_start();
        $controller = new TicketsController();
        $controller->index();
        $htmlInexistente = ob_get_clean();

        $this->assert(
            str_contains($htmlInexistente, 'Nenhum ticket encontrado'),
            "Alerta de 'Nenhum ticket encontrado' renderizado para busca sem resultados"
        );
        $this->assert(
            str_contains($htmlInexistente, 'title="Limpar Filtro"'),
            "Botão 'Limpar' presente permitindo resetar a pesquisa"
        );

        // --- 5. Filtro por Status do Ticket (status_ticket=impresso) ---
        echo "\n--- 5. Filtro por Status do Ticket ---\n";
        $ticketsImpressos = Ticket::buscarParaConsulta('', $dataTeste, $turnoTeste, 50, null, 'impresso');
        $this->assert(
            is_array($ticketsImpressos),
            "Ticket::buscarParaConsulta aceitou status_ticket='impresso' com sucesso"
        );

        // --- 6. Autocomplete de Sugestões em Tempo Real (/operacional/tickets/sugestoes) ---
        echo "\n--- 6. Autocomplete de Sugestões em Tempo Real ---\n";

        // Verifica que o HTML renderiza autocomplete="off" e o container do dropdown flutuante
        $this->assert(
            str_contains($htmlInicial, 'autocomplete="off"'),
            "Input de pesquisa possui autocomplete='off' para suprimir histórico nativo do navegador"
        );
        $this->assert(
            str_contains($htmlInicial, 'id="buscaSugestoesTicketsDropdown"'),
            "Container flutuante de sugestões (buscaSugestoesTicketsDropdown) presente no HTML"
        );
        $this->assert(
            str_contains($htmlInicial, '/operacional/tickets/sugestoes'),
            "Script frontend faz referência ao endpoint assíncrono de sugestões"
        );

        // Testa o método de Model: Ticket::buscarSugestoesConsulta
        $sugestoesModel = Ticket::buscarSugestoesConsulta('Enzo', $dataTeste, $turnoTeste, 8);
        $this->assert(
            is_array($sugestoesModel) && count($sugestoesModel) > 0,
            "Ticket::buscarSugestoesConsulta retornou resultados para 'Enzo' (" . count($sugestoesModel) . " sugestões)"
        );
        if (!empty($sugestoesModel)) {
            $primeira = $sugestoesModel[0];
            $this->assert(
                isset($primeira['aluno_nome'], $primeira['codigo_ticket'], $primeira['produto_descricao']),
                "Estrutura da sugestão contém aluno_nome, codigo_ticket e produto_descricao"
            );
        }

        // Testa busca de sugestão vazia
        $sugestoesVazia = Ticket::buscarSugestoesConsulta('', $dataTeste, $turnoTeste);
        $this->assert(
            empty($sugestoesVazia),
            "Ticket::buscarSugestoesConsulta com termo vazio retorna array vazio"
        );

        // Testa endpoint do Controller: TicketsController::sugestoes()
        $_GET = [
            'q'     => 'Enzo',
            'data'  => $dataTeste,
            'turno' => $turnoTeste,
        ];
        ob_start();
        $controller = new TicketsController();
        $controller->sugestoes();
        $jsonOutput = ob_get_clean();

        $dadosJson = json_decode($jsonOutput, true);
        $this->assert(
            is_array($dadosJson) && ($dadosJson['sucesso'] ?? false) === true,
            "Endpoint TicketsController::sugestoes retornou JSON com sucesso = true"
        );
        $this->assert(
            isset($dadosJson['sugestoes']) && is_array($dadosJson['sugestoes']),
            "Endpoint retornou array de sugestões estruturado"
        );

        echo "\n=================================================================\n";
        echo " RESULTADO: {$this->passou} aprovados, {$this->falhou} falhas.\n";
        echo "=================================================================\n\n";

        if ($this->falhou > 0) {
            exit(1);
        }
    }
}

$teste = new TicketsCargaSobDemandaTest();
$teste->executar();
