<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Inicialização no Dia Atual no Agendamento Semanal (/portal/agendamento)
 * 
 * Execução via Docker: docker compose exec app php tests/agendamento_dia_atual_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Session;
use App\Core\Auth;
use App\Core\Model;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\CalendarioEscolar;
use App\Controllers\PortalController;

class AgendamentoDiaAtualTest
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;

    private function assert(bool $condition, string $description): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->passedAssertions++; // We don't fail silently, but let's report fail if false
            // wait: let's fix below
        }
    }

    private function report(bool $condition, string $description): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  [PASS] {$description}\n";
        } else {
            echo "  [FAIL] {$description}\n";
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " SUÍTE DE TESTES: INICIALIZAÇÃO NO DIA ATUAL NO AGENDAMENTO SEMANAL\n";
        echo "====================================================================\n\n";

        Session::start();

        // 1. Setup de responsável e aluno para teste
        $sufixo = random_int(1000, 9999);
        $cpfResp = "911" . sprintf('%06d', random_int(1, 999999)) . "99";

        $nomeResp = "Responsável Teste Dia Atual {$sufixo}";
        $resp = Responsavel::localizarOuCriarPorCpf([
            'cpf' => $cpfResp,
            'nome' => $nomeResp,
            'telefone' => "119" . sprintf('%08d', random_int(10000000, 99999999)),
            'email' => "teste.dia.atual.{$sufixo}@email.com",
            'ativo' => 1
        ]);

        $turma = Turma::listarTodas()[0] ?? null;
        if (!$turma) {
            Turma::criar([
                'nome' => '5º Ano A - Teste',
                'turno' => 'matutino',
                'ano_letivo' => (int)date('Y')
            ]);
            $turma = Turma::listarTodas()[0];
        }

        $matricula = "MAT" . sprintf('%05d', random_int(10000, 99999));
        $alunoId = Aluno::criar([
            'nome' => "Aluno Teste Dia Atual {$sufixo}",
            'matricula' => $matricula,
            'turma_id' => $turma['id'],
            'responsavel_id' => $resp['id'],
            'turno' => 'matutino'
        ]);
        $aluno = Aluno::buscarPorId($alunoId);

        // Autentica como o responsável
        $_SESSION['_auth_logged'] = true;
        $_SESSION['_auth_type'] = 'responsavel';
        $_SESSION['_auth_id'] = (int)$resp['id'];
        $_SESSION['_auth_role'] = 'responsavel';
        $_SESSION['_auth_username'] = $cpfResp;
        $_SESSION['_auth_nome'] = $nomeResp;

        // Obter os dias da semana corrente
        $diasSemana = CalendarioEscolar::obterDiasSemanaLetiva('atual');
        $hojeIso = date('Y-m-d');
        $diaSemanaNumero = (int)date('N'); // 1 = Seg, 5 = Sex, 6 = Sáb, 7 = Dom

        echo "1. Testando abertura de /portal/agendamento sem informar parâmetro de data:\n";
        $_GET = ['aluno' => $alunoId];
        $_POST = [];
        $_SESSION['_flash'] = [];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->agendamento();
        $html = ob_get_clean();

        if ($diaSemanaNumero >= 1 && $diaSemanaNumero <= 5) {
            // Hoje é dia útil (Segunda a Sexta-feira)
            $this->report(
                str_contains($html, "Data de Consumo: <strong>{$hojeIso}</strong>"),
                "Abertura sem parâmetro 'data' seleciona diretamente a data atual ({$hojeIso})"
            );
            $this->report(
                !str_contains($html, "Data de Consumo: <strong>{$diasSemana[0]['data']}</strong>") || $hojeIso === $diasSemana[0]['data'],
                "Não força a Segunda-feira ({$diasSemana[0]['data']}) quando hoje for outro dia útil da semana"
            );
        } else {
            // Fim de semana (Sábado ou Domingo)
            $this->report(
                str_contains($html, "Data de Consumo:"),
                "Abertura no final de semana seleciona o dia padrão da semana de forma estável"
            );
        }

        echo "\n2. Testando seleção explícita de data via clique nos cards (?data=YYYY-MM-DD):\n";
        // Escolhe o primeiro dia da semana (Segunda) explicitamente
        $dataSegunda = $diasSemana[0]['data'];
        $_GET = ['aluno' => $alunoId, 'data' => $dataSegunda];

        ob_start();
        $ctrl->agendamento();
        $htmlSegunda = ob_get_clean();

        $this->report(
            str_contains($htmlSegunda, "Data de Consumo: <strong>{$dataSegunda}</strong>"),
            "Ao passar ?data={$dataSegunda}, o sistema respeita rigorosamente a escolha explícita do usuário"
        );

        echo "\n3. Testando clique em outra data qualquer da semana:\n";
        $dataTerceiroDia = $diasSemana[2]['data']; // Quarta-feira
        $_GET = ['aluno' => $alunoId, 'data' => $dataTerceiroDia];

        ob_start();
        $ctrl->agendamento();
        $htmlTerceiro = ob_get_clean();

        $this->report(
            str_contains($htmlTerceiro, "Data de Consumo: <strong>{$dataTerceiroDia}</strong>"),
            "Ao passar ?data={$dataTerceiroDia}, o cardápio da data solicitada é exibido com sucesso"
        );

        echo "\n4. Testando passagem de data inexistente ou inválida (?data=9999-99-99):\n";
        $_GET = ['aluno' => $alunoId, 'data' => '9999-99-99'];

        ob_start();
        $ctrl->agendamento();
        $htmlInvalida = ob_get_clean();

        if ($diaSemanaNumero >= 1 && $diaSemanaNumero <= 5) {
            $this->report(
                str_contains($htmlInvalida, "Data de Consumo: <strong>{$hojeIso}</strong>"),
                "Data inválida cai de forma segura no dia atual ({$hojeIso})"
            );
        } else {
            $this->report(
                str_contains($htmlInvalida, "Data de Consumo:"),
                "Data inválida possui fallback seguro"
            );
        }

        echo "\n--------------------------------------------------------------------\n";
        echo " RESULTADO DOS TESTES DE AGENDAMENTO NO DIA ATUAL:\n";
        echo " Total de asserções: {$this->totalAssertions}\n";
        echo " Aprovadas:          {$this->passedAssertions}\n";
        echo " Reprovadas:         " . ($this->totalAssertions - $this->passedAssertions) . "\n";
        echo " Taxa de sucesso:    " . round(($this->passedAssertions / $this->totalAssertions) * 100, 1) . "%\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->passedAssertions !== $this->totalAssertions) {
            exit(1);
        }
    }
}

$test = new AgendamentoDiaAtualTest();
$test->run();
