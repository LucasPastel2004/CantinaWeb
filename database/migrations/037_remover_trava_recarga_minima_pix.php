<?php
/**
 * Migration 037 — Remove valor mínimo de recarga via Pix
 * 
 * Cantina Web — Colégio Monteiro Lobato
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE `configuracoes_globais` 
            SET `recarga_minima_pix` = 0.00;
        ");
        $pdo->exec("
            ALTER TABLE `configuracoes_globais` 
            MODIFY COLUMN `recarga_minima_pix` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `configuracoes_globais` 
            MODIFY COLUMN `recarga_minima_pix` DECIMAL(10,2) NOT NULL DEFAULT 10.00;
        ");
    }
};
