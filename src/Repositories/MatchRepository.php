<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findNextByBaba(int $babaId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, title, starts_at, location, status
             FROM matches
             WHERE baba_id = :baba_id
               AND status = 'scheduled'
             ORDER BY starts_at ASC
             LIMIT 1"
        );
        $stmt->execute([':baba_id' => $babaId]);
        $match = $stmt->fetch();
        return $match ?: null;
    }

    public function listByBaba(int $babaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, title, starts_at, location, status
             FROM matches
             WHERE baba_id = :baba_id
             ORDER BY starts_at DESC"
        );
        $stmt->execute([':baba_id' => $babaId]);
        return $stmt->fetchAll();
    }

    public function create(int $babaId, int $createdBy, string $title, string $startsAt, string $location): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO matches (baba_id, title, starts_at, location, created_by)
             VALUES (:baba_id, :title, :starts_at, :location, :created_by)'
        );
        $stmt->execute([
            ':baba_id' => $babaId,
            ':title' => $title,
            ':starts_at' => $startsAt,
            ':location' => $location,
            ':created_by' => $createdBy,
        ]);
    }

    public function upsertAttendanceForUser(int $matchId, int $babaId, int $userId, string $status): bool
    {
        $playerStmt = $this->db->prepare(
            'SELECT id FROM players WHERE baba_id = :baba_id AND user_id = :user_id LIMIT 1'
        );
        $playerStmt->execute([
            ':baba_id' => $babaId,
            ':user_id' => $userId,
        ]);
        $playerId = $playerStmt->fetchColumn();
        if ($playerId === false) {
            return false;
        }

        $confirmedAt = $status === 'pending' ? null : date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            'INSERT INTO match_attendances (match_id, player_id, status, confirmed_at)
             VALUES (:match_id, :player_id, :status, :confirmed_at)
             ON DUPLICATE KEY UPDATE status = VALUES(status), confirmed_at = VALUES(confirmed_at)'
        );
        $stmt->execute([
            ':match_id' => $matchId,
            ':player_id' => (int) $playerId,
            ':status' => $status,
            ':confirmed_at' => $confirmedAt,
        ]);

        return true;
    }

    public function attendanceSummary(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS total
             FROM match_attendances
             WHERE match_id = :match_id
             GROUP BY status"
        );
        $stmt->execute([':match_id' => $matchId]);
        $rows = $stmt->fetchAll();

        $summary = ['confirmed' => 0, 'out' => 0, 'pending' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['total'];
        }
        return $summary;
    }
}
