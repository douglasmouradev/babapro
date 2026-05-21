<?php

declare(strict_types=1);

loadEnv(dirname(__DIR__) . '/.env');

date_default_timezone_set(env('APP_TIMEZONE', 'America/Bahia'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !in_array(env('APP_ENV', 'local'), ['local', 'development'], true),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function env(string $key, ?string $default = null): ?string
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * URL publica para assets (CSS, imagens). Se o site nao usa public/ como raiz,
 * prefixa /public automaticamente quando o arquivo existir la.
 */
function asset_url(string $path): string
{
    $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

    if ($docRoot !== '') {
        if (is_file($docRoot . $path)) {
            return $path;
        }
        if (is_file($docRoot . '/public' . $path)) {
            return '/public' . $path;
        }
    }

    $projectPublicFile = dirname(__DIR__) . '/public' . $path;
    if (is_file($projectPublicFile)) {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (!str_contains($script, '/public/')) {
            return '/public' . $path;
        }
    }

    return $path;
}

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION['_csrf_token'] ?? '';
    return is_string($sessionToken) && hash_equals($sessionToken, $token);
}

/**
 * Iniciais para avatar fallback (UTF-8 quando mbstring disponivel).
 */
function user_initials(string $fullName): string
{
    $fullName = trim($fullName);
    if ($fullName === '') {
        return '?';
    }
    $fullName = (string) preg_replace('/\([^)]*\)/u', '', $fullName);
    $fullName = trim($fullName);
    if ($fullName === '') {
        return '?';
    }
    $stopWords = ['de', 'da', 'do', 'dos', 'das', 'e'];

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return '?';
        }
        $meaningful = [];
        foreach ($parts as $p) {
            if (!in_array(mb_strtolower($p, 'UTF-8'), $stopWords, true)) {
                $meaningful[] = $p;
            }
        }
        if ($meaningful === []) {
            $meaningful = $parts;
        }
        $slice = array_slice($meaningful, 0, 2);
        $out = '';
        foreach ($slice as $p) {
            $out .= mb_strtoupper(mb_substr($p, 0, 1), 'UTF-8');
        }
        return $out !== '' ? $out : '?';
    }
    $parts = preg_split('/\s+/', $fullName);
    $meaningful = [];
    foreach ($parts as $p) {
        if (!in_array(strtolower($p), $stopWords, true)) {
            $meaningful[] = $p;
        }
    }
    if ($meaningful === []) {
        $meaningful = $parts;
    }
    $a = strtoupper(substr((string) ($meaningful[0] ?? ''), 0, 1));
    $b = isset($meaningful[1]) ? strtoupper(substr((string) $meaningful[1], 0, 1)) : '';
    return ($a . $b) !== '' ? $a . $b : '?';
}

function clientIp(): string
{
    $candidates = [
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['HTTP_X_REAL_IP'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $value) {
        if (!is_string($value) || $value === '') {
            continue;
        }
        $ip = trim(explode(',', $value)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }

    return '0.0.0.0';
}
