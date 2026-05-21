<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use PDO;
use RuntimeException;

final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByPhoneAndBabaCode(string $phone, string $babaCode): ?array
    {
        $sql = <<<SQL
            SELECT
                u.id,
                u.full_name,
                u.phone,
                u.pin_hash,
                u.photo_path,
                u.global_role,
                u.status,
                m.role AS membership_role,
                b.id AS baba_id,
                b.name AS baba_name,
                b.code AS baba_code,
                b.photo_path AS baba_photo_path,
                b.welcome_message AS baba_welcome_message
            FROM users u
            INNER JOIN baba_members m ON m.user_id = u.id AND m.status = 'active'
            INNER JOIN babas b ON b.id = m.baba_id AND b.status = 'active'
            WHERE u.phone = :phone
              AND b.code = :baba_code
              AND u.status = 'active'
            LIMIT 1
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':phone' => $phone,
            ':baba_code' => strtoupper($babaCode),
        ]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function listByBaba(int $babaId, array $filters = []): array
    {
        $where = ['m.baba_id = :baba_id', 'u.status = :user_status'];
        $params = [
            ':baba_id' => $babaId,
            ':user_status' => 'active',
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(u.full_name LIKE :search OR u.phone LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $access = $filters['access'] ?? '';
        if (in_array($access, ['active', 'inactive', 'blocked'], true)) {
            $where[] = 'm.status = :access';
            $params[':access'] = $access;
        }

        $payment = $filters['payment'] ?? '';
        if (in_array($payment, ['adimplente', 'inadimplente'], true)) {
            $where[] = 'm.payment_status = :payment';
            $params[':payment'] = $payment;
        }

        $sql = <<<SQL
            SELECT
                u.id,
                u.full_name,
                u.phone,
                u.photo_path,
                u.global_role,
                m.role AS membership_role,
                m.status AS membership_status,
                m.payment_status,
                u.created_at
            FROM baba_members m
            INNER JOIN users u ON u.id = m.user_id
            WHERE %s
            ORDER BY u.created_at DESC
        SQL;

        $stmt = $this->db->prepare(sprintf($sql, implode(' AND ', $where)));
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cria ou vincula usuario ao baba. Retorna o id do usuario (existente ou novo).
     */
    public function createOrAttachToBaba(
        int $babaId,
        string $fullName,
        string $phone,
        string $pin,
        string $membershipRole,
        string $membershipStatus,
        string $paymentStatus,
        string $registeredDate,
        string $registeredTime
    ): int {
        $timestamp = DateTimeImmutable::createFromFormat('Y-m-d H:i', $registeredDate . ' ' . $registeredTime);
        if ($timestamp === false) {
            throw new RuntimeException('Data/hora de cadastro invalida.');
        }

        $createdAt = $timestamp->format('Y-m-d H:i:s');

        $this->db->beginTransaction();
        try {
            $userId = $this->lookupUserIdByPhone($phone);

            if ($userId === null) {
                $insertUser = $this->db->prepare(
                    'INSERT INTO users (full_name, phone, pin_hash, global_role, created_at) VALUES (:full_name, :phone, :pin_hash, :global_role, :created_at)'
                );
                $insertUser->execute([
                    ':full_name' => $fullName,
                    ':phone' => $phone,
                    ':pin_hash' => password_hash($pin, PASSWORD_DEFAULT),
                    ':global_role' => 'user',
                    ':created_at' => $createdAt,
                ]);
                $userId = (int) $this->db->lastInsertId();
            }

            $attachMembership = $this->db->prepare(
                'INSERT INTO baba_members (baba_id, user_id, role, status, payment_status, joined_at)
                 VALUES (:baba_id, :user_id, :role, :status, :payment_status, :joined_at)
                 ON DUPLICATE KEY UPDATE role = VALUES(role), status = VALUES(status), payment_status = VALUES(payment_status), joined_at = VALUES(joined_at)'
            );
            $attachMembership->execute([
                ':baba_id' => $babaId,
                ':user_id' => $userId,
                ':role' => $membershipRole,
                ':status' => $membershipStatus,
                ':payment_status' => $paymentStatus,
                ':joined_at' => $createdAt,
            ]);

            $this->ensurePlayerExists($babaId, $userId, $fullName);

            $this->db->commit();
            return $userId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function setUserPhoto(int $userId, string $relativeWebPath): void
    {
        $stmt = $this->db->prepare('UPDATE users SET photo_path = :path WHERE id = :id');
        $stmt->execute([
            ':path' => $relativeWebPath,
            ':id' => $userId,
        ]);
    }

    /**
     * Membros ativos do baba para faixa de fotos (ordem alfabetica).
     *
     * @return list<array{id: int, full_name: string, photo_path: ?string}>
     */
    public function listBabaMemberFaces(int $babaId): array
    {
        $sql = <<<SQL
            SELECT u.id, u.full_name, u.photo_path
            FROM baba_members m
            INNER JOIN users u ON u.id = m.user_id
            WHERE m.baba_id = :baba_id
              AND m.status = 'active'
              AND u.status = 'active'
            ORDER BY u.full_name ASC
        SQL;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':baba_id' => $babaId]);
        /** @var list<array{id: int, full_name: string, photo_path: ?string}> */
        return $stmt->fetchAll();
    }

    public function findUserIdByPhone(string $phone): ?int
    {
        return $this->lookupUserIdByPhone($phone);
    }

    public function updateMembershipSettings(
        int $babaId,
        int $userId,
        string $membershipRole,
        string $membershipStatus,
        string $paymentStatus
    ): void {
        $stmt = $this->db->prepare(
            'UPDATE baba_members
             SET role = :role,
                 status = :status,
                 payment_status = :payment_status
             WHERE baba_id = :baba_id
               AND user_id = :user_id'
        );
        $stmt->execute([
            ':role' => $membershipRole,
            ':status' => $membershipStatus,
            ':payment_status' => $paymentStatus,
            ':baba_id' => $babaId,
            ':user_id' => $userId,
        ]);
    }

    public function resetUserPin(int $userId, string $pin): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET pin_hash = :pin_hash WHERE id = :user_id'
        );
        $stmt->execute([
            ':pin_hash' => password_hash($pin, PASSWORD_DEFAULT),
            ':user_id' => $userId,
        ]);
    }

    public function countByBaba(int $babaId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM baba_members m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.baba_id = :baba_id
               AND u.status = :user_status'
        );
        $stmt->execute([
            ':baba_id' => $babaId,
            ':user_status' => 'active',
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function isLoginBlocked(string $phone, string $ip): bool
    {
        $stmt = $this->db->prepare(
            'SELECT MAX(blocked_until) AS blocked_until
             FROM login_attempts
             WHERE phone = :phone
               AND ip_address = :ip
               AND success = 0'
        );
        $stmt->execute([
            ':phone' => $phone,
            ':ip' => $ip,
        ]);
        $blockedUntil = $stmt->fetchColumn();
        return is_string($blockedUntil) && $blockedUntil !== '' && strtotime($blockedUntil) > time();
    }

    public function registerLoginAttempt(string $phone, string $babaCode, string $ip, bool $success): void
    {
        $blockedUntil = null;

        if (!$success) {
            $countStmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM login_attempts
                 WHERE phone = :phone
                   AND ip_address = :ip
                   AND success = 0
                   AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );
            $countStmt->execute([
                ':phone' => $phone,
                ':ip' => $ip,
            ]);
            $failCount = (int) $countStmt->fetchColumn();
            if ($failCount + 1 >= 5) {
                $blockedUntil = date('Y-m-d H:i:s', time() + (15 * 60));
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (phone, baba_code, ip_address, success, blocked_until)
             VALUES (:phone, :baba_code, :ip, :success, :blocked_until)'
        );
        $stmt->execute([
            ':phone' => $phone,
            ':baba_code' => $babaCode,
            ':ip' => $ip,
            ':success' => $success ? 1 : 0,
            ':blocked_until' => $blockedUntil,
        ]);
    }

    public function createAuditLog(
        ?int $actorUserId,
        ?int $babaId,
        string $action,
        ?int $targetUserId,
        array $details,
        string $ipAddress
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (actor_user_id, baba_id, action, target_user_id, details_json, ip_address)
             VALUES (:actor_user_id, :baba_id, :action, :target_user_id, :details_json, :ip_address)'
        );
        $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':baba_id' => $babaId,
            ':action' => $action,
            ':target_user_id' => $targetUserId,
            ':details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':ip_address' => $ipAddress,
        ]);
    }

    private function lookupUserIdByPhone(string $phone): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private function ensurePlayerExists(int $babaId, int $userId, string $fullName): void
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM players WHERE baba_id = :baba_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute([
            ':baba_id' => $babaId,
            ':user_id' => $userId,
        ]);

        if ($stmt->fetchColumn() !== false) {
            return;
        }

        $nickname = trim(explode(' ', $fullName)[0] ?? $fullName);

        $insert = $this->db->prepare(
            "INSERT INTO players (baba_id, user_id, nickname, preferred_position, overall, status)
             VALUES (:baba_id, :user_id, :nickname, 'meia', 50, 'active')"
        );
        $insert->execute([
            ':baba_id' => $babaId,
            ':user_id' => $userId,
            ':nickname' => $nickname,
        ]);
    }
}
