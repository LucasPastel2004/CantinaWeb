<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Adaptador de Integração: Serviço de E-mail Institucional (Hostnet & Local)
 * 
 * Envio transacional de códigos de segurança e notificações via E-mail
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class EmailService
{
    /**
     * Memória estática de códigos enviados para modo mock/testes
     */
    private static array $mockSentCodes = [];

    /**
     * Gera um código numérico de 6 dígitos com segurança criptográfica
     */
    public static function generateCode(): string
    {
        return (string)random_int(100000, 999999);
    }

    /**
     * Mascara o endereço de e-mail para exibição segura na interface (ex: l***s@gmail.com)
     */
    public static function maskEmail(string $email): string
    {
        $email = trim($email);
        if (empty($email) || !str_contains($email, '@')) {
            return '—';
        }

        $partes = explode('@', $email, 2);
        $user = $partes[0];
        $domain = $partes[1] ?? '';

        $len = mb_strlen($user);
        if ($len <= 2) {
            $maskedUser = mb_substr($user, 0, 1) . '***';
        } else {
            $primeiro = mb_substr($user, 0, 1);
            $ultimo = mb_substr($user, -1);
            $maskedUser = $primeiro . '***' . $ultimo;
        }

        return $maskedUser . '@' . $domain;
    }

    /**
     * Envia código de verificação para Primeiro Acesso
     */
    public function sendFirstAccessCode(string $email, string $code, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $assunto = "Código de Primeiro Acesso — Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Olá, <strong>{$primeiroNome}</strong>!
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            Recebemos a sua solicitação de primeiro acesso ao <strong>Portal da Família</strong> da Cantina Web do Colégio Monteiro Lobato. Utilize o código de segurança abaixo para confirmar sua conta:
        </p>
        <div style="background-color: #f8fafc; border: 1px dashed #94a3b8; border-radius: 8px; padding: 18px 24px; text-align: center; margin-bottom: 24px;">
            <span style="font-family: monospace, Courier, sans-serif; font-size: 32px; font-weight: 700; letter-spacing: 10px; color: #1d4ed8; display: inline-block;">
                {$code}
            </span>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Este código expira em <strong>15 minutos</strong>.
            </div>
        </div>
        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 0;">
            Se você não realizou esta solicitação no Portal da Família, por favor desconsidere este e-mail.
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), $code, 'primeiro_acesso');
    }

    /**
     * Envia código de verificação para Redefinição de Senha
     */
    public function sendPasswordResetCode(string $email, string $code, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $assunto = "Código para Redefinição de Senha — Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Olá, <strong>{$primeiroNome}</strong>!
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            Você solicitou a redefinição de sua senha de acesso ao <strong>Portal da Família</strong>. Digite o código de 6 dígitos abaixo para cadastrar sua nova senha:
        </p>
        <div style="background-color: #f8fafc; border: 1px dashed #94a3b8; border-radius: 8px; padding: 18px 24px; text-align: center; margin-bottom: 24px;">
            <span style="font-family: monospace, Courier, sans-serif; font-size: 32px; font-weight: 700; letter-spacing: 10px; color: #1d4ed8; display: inline-block;">
                {$code}
            </span>
            <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
                Este código expira em <strong>15 minutos</strong>.
            </div>
        </div>
        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 0;">
            Se você não solicitou esta redefinição, recomendamos trocar suas credenciais imediatamente ou avisar a cantina.
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), $code, 'reset_senha');
    }

    /**
     * Envia confirmação de recarga Pix creditada na carteira familiar
     */
    public function sendRecargaConfirmada(string $email, float $valor, float $novoSaldo, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $valorFmt = number_format($valor, 2, ',', '.');
        $saldoFmt = number_format($novoSaldo, 2, ',', '.');
        $assunto = "Recarga Pix Confirmada — Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Olá, <strong>{$primeiroNome}</strong>!
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            Confirmamos o recebimento da sua recarga Pix na carteira digital da sua família. Os créditos já estão disponíveis para consumo e agendamentos:
        </p>
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="font-size: 13px; color: #64748b; padding: 6px 0;">Valor da Recarga:</td>
                    <td style="font-size: 15px; font-weight: 700; color: #16a34a; text-align: right; padding: 6px 0;">+ R$ {$valorFmt}</td>
                </tr>
                <tr>
                    <td style="font-size: 13px; color: #64748b; padding: 6px 0; border-top: 1px solid #e2e8f0;">Novo Saldo da Carteira:</td>
                    <td style="font-size: 16px; font-weight: 700; color: #0f172a; text-align: right; padding: 6px 0; border-top: 1px solid #e2e8f0;">R$ {$saldoFmt}</td>
                </tr>
            </table>
        </div>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">
            Acesse o Portal da Família a qualquer momento para acompanhar o extrato detalhado de consumo dos seus filhos.
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), null, 'recarga_confirmada');
    }

    /**
     * Envia alerta preventivo de saldo baixo na carteira familiar
     */
    public function sendAlertaSaldoBaixo(string $email, float $saldoAtual, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $saldoFmt = number_format($saldoAtual, 2, ',', '.');
        $assunto = "Alerta: Saldo Baixo na Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Olá, <strong>{$primeiroNome}</strong>!
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            O saldo da sua carteira familiar na Cantina Web atingiu <strong style="color: #ea580c;">R$ {$saldoFmt}</strong> (abaixo do limite preventivo de segurança).
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px;">
            Para garantir os lanches escolares dos seus filhos sem interrupções no recreio, realize uma recarga Pix rápida acessando o Portal da Família.
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), null, 'alerta_saldo_baixo');
    }

    /**
     * Envia notificação emergencial de contestação bancária (MED) e suspensão de conta
     */
    public function sendNotificacaoMed(string $email, float $valorEstornado, float $saldoNegativo, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $estornoFmt = number_format($valorEstornado, 2, ',', '.');
        $saldoFmt = number_format(abs($saldoNegativo), 2, ',', '.');
        $assunto = "Comunicado Importante: Suspensão Preventiva MED — Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Prezado(a) <strong>{$primeiroNome}</strong>,
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 16px;">
            Identificamos uma contestação bancária (Mecanismo Especial de Devolução — MED) no valor de <strong>R$ {$estornoFmt}</strong> referente a uma recarga Pix vinculada à sua conta.
        </p>
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <p style="font-size: 14px; color: #991b1b; margin: 0; line-height: 1.5;">
                Em decorrência do estorno, a carteira familiar encontra-se com saldo devedor de <strong>-R$ {$saldoFmt}</strong> e temporariamente suspensa para novos agendamentos de lanches escolares de todos os seus dependentes.
            </p>
        </div>
        <p style="font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 0;">
            Por favor, compareça à administração da cantina para regularização do saldo ou entre em contato com nosso atendimento para reativação imediata da conta.
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), null, 'notificacao_med');
    }

    /**
     * Envia notificação de regularização de débito e desbloqueio da conta familiar
     */
    public function sendNotificacaoDesbloqueioMed(string $email, float $novoSaldo, string $nome = ''): bool
    {
        $primeiroNome = htmlspecialchars(!empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável', ENT_QUOTES, 'UTF-8');
        $saldoFmt = number_format($novoSaldo, 2, ',', '.');
        $assunto = "Conta Regularizada com Sucesso — Cantina Web";

        $conteudoHtml = <<<HTML
        <p style="font-size: 15px; color: #1e293b; margin-bottom: 16px;">
            Olá, <strong>{$primeiroNome}</strong>!
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
            Confirmamos a regularização financeira da sua conta familiar na Cantina Web do Colégio Monteiro Lobato. A suspensão preventiva foi <strong>removida com sucesso</strong>.
        </p>
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <p style="font-size: 14px; color: #166534; margin: 0; line-height: 1.5;">
                Seu saldo atual disponível é de <strong>R$ {$saldoFmt}</strong> e os agendamentos de lanches já estão liberados para todos os seus dependentes.
            </p>
        </div>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 0;">
            Agradecemos pela cooperação e parceria contínua com nossa cantina escolar!
        </p>
HTML;

        return $this->sendEmail($email, $assunto, $this->buildTemplate($assunto, $conteudoHtml), null, 'desbloqueio_med');
    }

    /**
     * Dispara o e-mail via SMTP Socket Nativo, mail() ou armazena no provedor mock/log
     */
    public function sendEmail(string $destinatario, string $assunto, string $mensagemHtml, ?string $code = null, string $tipo = 'geral'): bool
    {
        $cleanEmail = trim($destinatario);

        if (empty($cleanEmail) || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("[EMAIL_ERROR] Destinatário inválido: {$destinatario}");
            return false;
        }

        // Armazena no mock para testes automatizados
        if ($code !== null) {
            self::$mockSentCodes[$cleanEmail] = [
                'code'       => $code,
                'type'       => $tipo,
                'created_at' => time()
            ];
        }

        // Carrega configurações da aplicação
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $emailConfig = $config['integracoes']['email'] ?? $config['email'] ?? [];
        $isDev = ($config['app']['env'] ?? '') === 'development' || ($config['app']['debug'] ?? false);
        $defaultProvedor = $isDev ? 'mock' : 'mail';

        $provedor = strtolower((string)($emailConfig['provedor'] ?? $defaultProvedor));
        $fromEmail = $emailConfig['from_email'] ?? 'contato@cantinawebmonteirolobato.com.br';
        $fromName = $emailConfig['from_name'] ?? 'Cantina Web — Colégio Monteiro Lobato';

        // Log sempre gravado para contingência e auditoria de desenvolvimento/suporte
        $this->logEmailContingencia($cleanEmail, $assunto, $tipo, $code);

        // Se estiver em modo mock ou sem credenciais
        if ($provedor === 'mock') {
            return true;
        }

        // Disparo via PHP nativo mail() (padrão otimizado para servidores Apache Hostnet)
        if ($provedor === 'mail') {
            return $this->enviarViaMailNativo($cleanEmail, $assunto, $mensagemHtml, $fromEmail, $fromName);
        }

        // Disparo via SMTP socket nativo (porta 587 STARTTLS ou 465 SSL)
        if ($provedor === 'smtp') {
            $smtpHost = $emailConfig['smtp_host'] ?? 'mail.cantinawebmonteirolobato.com.br';
            $smtpPort = (int)($emailConfig['smtp_port'] ?? 587);
            $smtpUser = $emailConfig['smtp_user'] ?? $fromEmail;
            $smtpPass = $emailConfig['smtp_pass'] ?? '';
            $smtpSecure = strtolower((string)($emailConfig['smtp_secure'] ?? 'tls'));

            // Se a senha estiver vazia, faz fallback seguro para mail() nativo
            if (empty($smtpPass)) {
                return $this->enviarViaMailNativo($cleanEmail, $assunto, $mensagemHtml, $fromEmail, $fromName);
            }

            return $this->enviarViaSmtpSocket($cleanEmail, $assunto, $mensagemHtml, $smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpSecure, $fromEmail, $fromName);
        }

        return true;
    }

    /**
     * Envio via função PHP nativa mail() com cabeçalhos estruturados e envelopamento Hostnet
     */
    private function enviarViaMailNativo(string $para, string $assunto, string $html, string $deEmail, string $deNome): bool
    {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        $headers[] = "From: =?UTF-8?B?" . base64_encode($deNome) . "?= <{$deEmail}>";
        $headers[] = "Reply-To: <{$deEmail}>";
        $headers[] = "X-Mailer: PHP/" . phpversion();

        $assuntoCodificado = "=?UTF-8?B?" . base64_encode($assunto) . "?=";
        $headersStr = implode("\r\n", $headers);
        $parametrosAdicionais = "-f{$deEmail}";

        try {
            $resultado = @mail($para, $assuntoCodificado, $html, $headersStr, $parametrosAdicionais);
            if (!$resultado) {
                // Tenta sem os parâmetros adicionais de sendmail
                $resultado = @mail($para, $assuntoCodificado, $html, $headersStr);
            }
            return (bool)$resultado;
        } catch (\Throwable $e) {
            error_log('[MAIL_NATIVE_ERROR] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envio via cliente SMTP Socket nativo puro (sem bibliotecas externas)
     */
    private function enviarViaSmtpSocket(
        string $para,
        string $assunto,
        string $html,
        string $host,
        int $port,
        string $user,
        string $pass,
        string $secure,
        string $fromEmail,
        string $fromName
    ): bool {
        // Normalização automática de Host: na Hostnet, o host de envio é 'smtp.dominio' (mail.dominio não existe no DNS)
        if (str_contains($host, 'cantinawebmonteirolobato.com.br')) {
            $host = 'smtp.cantinawebmonteirolobato.com.br';
        } elseif (str_starts_with($host, 'mail.') && @gethostbyname($host) === $host) {
            $host = 'smtp.' . substr($host, 5);
        }

        $timeout = 10;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        // Estratégia de conexão: tenta a porta configurada; se falhar, tenta a porta alternativa (587 <-> 465)
        $tentativasConexao = [
            ['port' => $port, 'secure' => $secure]
        ];
        if ($port === 587) {
            $tentativasConexao[] = ['port' => 465, 'secure' => 'ssl'];
        } elseif ($port === 465) {
            $tentativasConexao[] = ['port' => 587, 'secure' => 'tls'];
        }

        $socket = null;
        $portaConectada = $port;
        $secureConectado = $secure;

        foreach ($tentativasConexao as $tentativa) {
            $tPort = $tentativa['port'];
            $tSec = $tentativa['secure'];
            $remoteHost = ($tSec === 'ssl' || $tPort === 465) ? "ssl://{$host}" : "tcp://{$host}";

            $socket = @stream_socket_client($remoteHost . ':' . $tPort, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
            if ($socket) {
                $portaConectada = $tPort;
                $secureConectado = $tSec;
                break;
            }
        }

        if (!$socket) {
            error_log("[SMTP_SOCKET_CONNECT_ERROR] Falha de conexão socket com {$host}: {$errstr} - Caindo para mail() nativo");
            return $this->enviarViaMailNativo($para, $assunto, $html, $fromEmail, $fromName);
        }

        stream_set_timeout($socket, $timeout);

        $lerResposta = function() use ($socket): string {
            $resposta = '';
            while ($linha = fgets($socket, 515)) {
                $resposta .= $linha;
                if (isset($linha[3]) && $linha[3] === ' ') {
                    break;
                }
            }
            return $resposta;
        };

        $enviarComando = function(string $cmd) use ($socket, $lerResposta): string {
            fwrite($socket, $cmd . "\r\n");
            return $lerResposta();
        };

        try {
            // Saudação inicial
            $lerResposta();

            // EHLO inicial
            $enviarComando("EHLO " . gethostname());

            // STARTTLS se porta 587 ou modo TLS
            if ($secureConectado === 'tls' || $portaConectada === 587) {
                $resp = $enviarComando("STARTTLS");
                if (str_starts_with($resp, '220')) {
                    $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                        $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                    }
                    if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                        fclose($socket);
                        return $this->enviarViaMailNativo($para, $assunto, $html, $fromEmail, $fromName);
                    }
                    $enviarComando("EHLO " . gethostname());
                }
            }

            // Normalização de usuário para Hostnet (substituição de @ por = na autenticação SMTP)
            $usuariosTentativa = [];
            if (str_contains($host, 'cantinawebmonteirolobato.com.br') || str_contains($host, 'hostnet') || str_contains($host, 'k8.com.br')) {
                if (str_contains($user, '@')) {
                    $usuariosTentativa[] = str_replace('@', '=', $user);
                }
                $usuariosTentativa[] = $user;
            } else {
                $usuariosTentativa[] = $user;
                if (str_contains($user, '@')) {
                    $usuariosTentativa[] = str_replace('@', '=', $user);
                }
            }

            $autenticado = false;
            foreach ($usuariosTentativa as $tentativaUser) {
                $respAuth = $enviarComando("AUTH LOGIN");
                if (!str_starts_with($respAuth, '334')) {
                    continue;
                }

                $enviarComando(base64_encode($tentativaUser));
                $respPass = $enviarComando(base64_encode($pass));
                if (str_starts_with($respPass, '235')) {
                    $autenticado = true;
                    break;
                }
            }

            if (!$autenticado) {
                error_log("[SMTP_AUTH_ERROR] Falha de autenticação SMTP com usuário: {$user} - Caindo para mail() nativo");
                fclose($socket);
                return $this->enviarViaMailNativo($para, $assunto, $html, $fromEmail, $fromName);
            }

            // Envelope
            $enviarComando("MAIL FROM:<{$fromEmail}>");
            $respRcpt = $enviarComando("RCPT TO:<{$para}>");
            if (!str_starts_with($respRcpt, '250')) {
                error_log("[SMTP_RCPT_ERROR] Rejeição de destinatário: {$para} -> {$respRcpt}");
                fclose($socket);
                return false;
            }

            // Conteúdo
            $enviarComando("DATA");

            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "To: <{$para}>";
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "Reply-To: <{$fromEmail}>";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 8bit";
            $headers[] = "X-Mailer: PHP/" . phpversion();

            $mensagem = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
            $respData = $enviarComando($mensagem);

            $enviarComando("QUIT");
            fclose($socket);

            return str_starts_with($respData, '250');
        } catch (\Throwable $e) {
            error_log('[SMTP_EXCEPTION] ' . $e->getMessage());
            @fclose($socket);
            return $this->enviarViaMailNativo($para, $assunto, $html, $fromEmail, $fromName);
        }
    }

    /**
     * Monta o invólucro do template corporativo HTML institucional
     */
    private function buildTemplate(string $titulo, string $corpoHtml): string
    {
        $ano = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" style="max-width: 540px; width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <!-- Topbar Institucional -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 28px; text-align: left; border-bottom: 3px solid #1d4ed8;">
                            <span style="font-size: 15px; font-weight: 700; color: #ffffff; letter-spacing: 0.3px;">
                                Colégio Monteiro Lobato
                            </span>
                            <span style="font-size: 13px; color: #94a3b8; display: block; margin-top: 2px;">
                                Cantina Web &bull; Notificações Oficiais
                            </span>
                        </td>
                    </tr>
                    <!-- Corpo da Mensagem -->
                    <tr>
                        <td style="padding: 32px 28px;">
                            {$corpoHtml}
                        </td>
                    </tr>
                    <!-- Rodapé Institucional -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 28px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="font-size: 11px; color: #64748b; line-height: 1.5; margin: 0 0 6px 0;">
                                Este é um e-mail automático gerado pelo sistema <strong>Cantina Web</strong> do Colégio Monteiro Lobato.<br>
                                Por favor, não responda diretamente a esta mensagem.
                            </p>
                            <p style="font-size: 11px; color: #94a3b8; margin: 0;">
                                &copy; {$ano} Colégio Monteiro Lobato. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Registra o e-mail no arquivo de contingência storage/logs/email.log
     */
    private function logEmailContingencia(string $destinatario, string $assunto, string $tipo, ?string $code): void
    {
        $logPath = __DIR__ . '/../../storage/logs/email.log';
        if (is_dir(dirname($logPath))) {
            $logEntry = sprintf(
                "[%s] [EMAIL] [Destino: %s] [Tipo: %s] [Código: %s] [Assunto: %s]\n",
                date('Y-m-d H:i:s'),
                $destinatario,
                $tipo,
                $code ?? 'N/A',
                $assunto
            );
            @file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Retorna o último código gerado para um determinado e-mail (para testes automatizados e emulação)
     */
    public static function getLastCode(string $email): ?string
    {
        $cleanEmail = trim($email);
        if (isset(self::$mockSentCodes[$cleanEmail]['code'])) {
            return self::$mockSentCodes[$cleanEmail]['code'];
        }

        // Em requisições HTTP segregadas, recupera do log de contingência
        $logPath = __DIR__ . '/../../storage/logs/email.log';
        if (file_exists($logPath)) {
            $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    $line = $lines[$i];
                    if (str_contains($line, "[Destino: {$cleanEmail}]") && preg_match('/\[Código: (\d{6})\]/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Limpa o histórico de códigos do mock
     */
    public static function clearMockCodes(): void
    {
        self::$mockSentCodes = [];
    }
}
