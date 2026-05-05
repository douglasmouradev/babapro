<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function attemptLogin(string $phone, string $babaCode, string $pin): bool
    {
        $user = $this->users->findByPhoneAndBabaCode($phone, $babaCode);
        if ($user === null) {
            return false;
        }

        if (!password_verify($pin, $user['pin_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['auth'] = [
            'user_id' => (int) $user['id'],
            'user_name' => $user['full_name'],
            'phone' => $user['phone'],
            'baba_id' => (int) $user['baba_id'],
            'baba_name' => $user['baba_name'],
            'baba_code' => $user['baba_code'],
            'role' => $user['membership_role'],
            'global_role' => $user['global_role'],
        ];

        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['auth']['user_id'], $_SESSION['auth']['baba_id']);
    }

    public function user(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }

    public function canManageUsers(): bool
    {
        $auth = $this->user();
        if ($auth === null) {
            return false;
        }

        return ($auth['global_role'] ?? '') === 'owner_saas'
            || ($auth['role'] ?? '') === 'baba_admin';
    }
}
