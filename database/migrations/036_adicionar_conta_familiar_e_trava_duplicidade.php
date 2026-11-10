<?php
/**
 * Migration 036 — Conta Familiar Compartilhada (Pai e Mãe) e Trava Antiduplicidade de Pedidos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Verifica se a coluna titular_id já existe na tabela responsaveis
        $checkColResp = $pdo->query("SHOW COLUMNS FROM `responsaveis` LIKE 'titular_id'")->fetch();
        if (!$checkColResp) {
            $sql1 = "ALTER TABLE `responsaveis` 
                     ADD COLUMN `titular_id` INT UNSIGNED NULL AFTER `id`,
                     ADD COLUMN `parentesco` VARCHAR(30) NOT NULL DEFAULT 'titular' AFTER `nome`,
                     ADD CONSTRAINT `fk_responsaveis_titular` FOREIGN KEY (`titular_id`) REFERENCES `responsaveis` (`id`) ON DELETE SET NULL,
                     ADD INDEX `idx_responsaveis_titular` (`titular_id`);";
            $pdo->exec($sql1);
        }

        // 2. Verifica se a coluna autor_responsavel_id já existe na tabela pedidos
        $checkColPed = $pdo->query("SHOW COLUMNS FROM `pedidos` LIKE 'autor_responsavel_id'")->fetch();
        if (!$checkColPed) {
            $sql2 = "ALTER TABLE `pedidos` 
                     ADD COLUMN `autor_responsavel_id` INT UNSIGNED NULL AFTER `responsavel_id`,
                     ADD CONSTRAINT `fk_pedidos_autor` FOREIGN KEY (`autor_responsavel_id`) REFERENCES `responsaveis` (`id`) ON DELETE SET NULL;";
            $pdo->exec($sql2);
        }
    }

    public function down(\PDO $pdo): void
    {
        $checkColPed = $pdo->query("SHOW COLUMNS FROM `pedidos` LIKE 'autor_responsavel_id'")->fetch();
        if ($checkColPed) {
            $pdo->exec("ALTER TABLE `pedidos` DROP FOREIGN KEY `fk_pedidos_autor`, DROP COLUMN `autor_responsavel_id`;");
        }

        $checkColResp = $pdo->query("SHOW COLUMNS FROM `responsaveis` LIKE 'titular_id'")->fetch();
        if ($checkColResp) {
            $pdo->exec("ALTER TABLE `responsaveis` DROP FOREIGN KEY `fk_responsaveis_titular`, DROP COLUMN `parentesco`, DROP COLUMN `titular_id`;");
        }
    }
};
