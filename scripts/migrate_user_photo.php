<?php

/**
 * Aplica a coluna users.photo_path se ainda nao existir.
 * Uso: php scripts/migrate_user_photo.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$check = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = :t
       AND COLUMN_NAME = :c'
);
$check->execute([':t' => 'users', ':c' => 'photo_path']);
$exists = (int) $check->fetchColumn() > 0;

if ($exists) {
    fwrite(STDOUT, "OK: coluna users.photo_path ja existe.\n");
    exit(0);
}

$pdo->exec('ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL AFTER pin_hash');
fwrite(STDOUT, "OK: coluna users.photo_path criada.\n");
exit(0);
