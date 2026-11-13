<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador Inicial e de Status Operacional
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;

class HomeController extends Controller
{
    /**
     * Tela inicial de status do sistema e validação da arquitetura MVC
     */
    public function index(): void
    {
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $isProduction = ($config['app']['env'] ?? 'development') === 'production';

        // Se o usuário já estiver autenticado, redireciona para seu painel oficial
        if (\App\Core\Auth::check()) {
            $this->redirect(\App\Core\Auth::isResponsavel() ? '/portal' : '/admin');
            return;
        }

        // Em produção, a raiz redireciona para a tela de login
        if ($isProduction) {
            $this->redirect('/login');
            return;
        }

        $data = [
            'appName'     => $config['app']['name'] ?? 'Cantina Web',
            'environment' => $config['app']['env'] ?? 'development',
            'phpVersion'  => PHP_VERSION,
            'faseAtual'   => 'Sistema Completo e Homologado (24 Fases)',
            'timestamp'   => date('d/m/Y H:i:s'),
            'sessionActive' => session_status() === PHP_SESSION_ACTIVE,
            'csrfToken'   => Session::getCsrfToken()
        ];

        $this->render('home/index', $data);
    }

    /**
     * Endpoint da API de status do sistema em JSON
     */
    public function status(): void
    {
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $isProduction = ($config['app']['env'] ?? 'development') === 'production';

        $opcacheReset = false;
        if (function_exists('opcache_reset')) {
            $lockFile = __DIR__ . '/../../storage/cache/opcache_reset.lock';
            $now = time();
            $lastReset = file_exists($lockFile) ? (int)@file_get_contents($lockFile) : 0;
            if (!$isProduction || ($now - $lastReset) >= 30) {
                $opcacheReset = @opcache_reset();
                if (!is_dir(dirname($lockFile))) {
                    @mkdir(dirname($lockFile), 0755, true);
                }
                @file_put_contents($lockFile, (string)$now, LOCK_EX);
            }
        }

        if ($isProduction) {
            $this->json([
                'status'        => 'online',
                'opcache_reset' => $opcacheReset,
                'timestamp'     => date('Y-m-d H:i:s')
            ]);
            return;
        }

        $this->json([
            'status'        => 'online',
            'opcache_reset' => $opcacheReset,
            'app'           => $config['app']['name'] ?? 'Cantina Web',
            'environment'   => $config['app']['env'] ?? 'development',
            'php_version'   => PHP_VERSION,
            'fase_atual'  => 'Sistema Completo e Homologado (24 Fases)',
            'mvc_core'    => [
                'router'       => 'App\Core\Router',
                'controller'   => 'App\Core\Controller',
                'model'        => 'App\Core\Model',
                'session'      => 'App\Core\Session',
                'errorHandler' => 'App\Core\ErrorHandler'
            ],
            'timestamp'   => date('Y-m-d H:i:s')
        ]);
    }
}
