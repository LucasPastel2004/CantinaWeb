<?php
/**
 * Migration 035 ? Atualiza o limite de alerta de saldo baixo para R$ 5,00
 * 
 * Cantina Web ? Col?gio Monteiro Lobato
 */

defined('CANTINA_SISTEMA') or die('Acesso direto n?o permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            UPDATE `configuracoes_globais` 
            SET `alerta_saldo_baixo` = 5.00;
        ");
        $pdo->exec("
            ALTER TABLE `configuracoes_globais` 
            MODIFY COLUMN `alerta_saldo_baixo` DECIMAL(10,2) NOT NULL DEFAULT 5.00;
        ");
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("
            ALTER TABLE `configuracoes_globais` 
            MODIFY COLUMN `alerta_saldo_baixo` DECIMAL(10,2) NOT NULL DEFAULT 15.00;
        ");
    }
};
