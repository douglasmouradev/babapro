<?php

/**
 * Aplica photo_path e welcome_message em babas se ainda nao existirem.
 * Uso: php scripts/migrate_baba_branding.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$columns = [
    'photo_path' => 'ALTER TABLE babas ADD COLUMN photo_path VARCHAR(255) NULL AFTER code',
    'welcome_message' => 'ALTER TABLE babas ADD COLUMN welcome_message TEXT NULL AFTER photo_path',
];

foreach ($columns as $name => $sql) {
    $check = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :t
           AND COLUMN_NAME = :c'
    );
    $check->execute([':t' => 'babas', ':c' => $name]);
    if ((int) $check->fetchColumn() > 0) {
        fwrite(STDOUT, "OK: babas.{$name} ja existe.\n");
        continue;
    }
    $pdo->exec($sql);
    fwrite(STDOUT, "OK: babas.{$name} criada.\n");
}

$upd = $pdo->prepare(
    "UPDATE babas SET welcome_message = :msg
     WHERE code = 'BABA10' AND (welcome_message IS NULL OR TRIM(welcome_message) = '')"
);
$upd->execute([
    ':msg' => 'Fala, time! O Baba de Quinta esta no ar. Bora aquecer, confirmar presenca e fazer acontecer mais um jogo epico. Boas vendas na quadra!',
]);

fwrite(STDOUT, "Migracao de branding do baba concluida.\n");
