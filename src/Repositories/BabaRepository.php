<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class BabaRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $babaId): ?array
    {
        $sql = <<<SQL
            SELECT
                id,
                name,
                code,
                photo_path,
                welcome_message,
                status
            FROM babas
            WHERE id = :id
              AND status = 'active'
            LIMIT 1
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $babaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
