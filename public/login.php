<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;

$auth = new AuthService(new UserRepository(Database::connection()));
$users = new UserRepository(Database::connection());

if ($auth->isAuthenticated()) {
    redirect('/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf'] ?? null)) {
        $error = 'Sessao expirada. Atualize a pagina e tente novamente.';
    } else {
    $phone = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    $babaCode = strtoupper(trim($_POST['baba_code'] ?? ''));
    $pin = trim($_POST['pin'] ?? '');
    $ip = clientIp();

        if ($phone === '' || $babaCode === '' || strlen($pin) !== 4) {
            $error = 'Preencha telefone, codigo do baba e PIN de 4 digitos.';
        } elseif ($users->isLoginBlocked($phone, $ip)) {
            $error = 'Muitas tentativas. Aguarde 15 minutos para tentar novamente.';
        } elseif (!$auth->attemptLogin($phone, $babaCode, $pin)) {
            $users->registerLoginAttempt($phone, $babaCode, $ip, false);
            $users->createAuditLog(
                null,
                null,
                'login_failed',
                null,
                ['phone' => $phone, 'baba_code' => $babaCode],
                $ip
            );
            $error = 'Credenciais invalidas.';
        } else {
            $users->registerLoginAttempt($phone, $babaCode, $ip, true);
            $sessionUser = $auth->user();
            $users->createAuditLog(
                (int) ($sessionUser['user_id'] ?? 0),
                (int) ($sessionUser['baba_id'] ?? 0),
                'login_success',
                (int) ($sessionUser['user_id'] ?? 0),
                ['phone' => $phone, 'baba_code' => $babaCode],
                $ip
            );
            redirect('/dashboard.php');
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Login</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .container {
            width: 100%;
            max-width: 430px;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 22px;
            background: linear-gradient(180deg, rgba(16,24,39,0.96) 0%, rgba(7,11,19,0.96) 100%);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
            margin: 24px auto;
        }

        .brand {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid #31425f;
            border-radius: 14px;
            background: linear-gradient(90deg, #0e1625 0%, #111d31 100%);
        }

        .brand strong {
            display: block;
            font-size: 24px;
            letter-spacing: 0.02em;
        }

        .brand span {
            color: var(--text-soft);
            font-size: 13px;
        }

        .login-grid { display: grid; gap: 12px; }
        .error { margin-top: 14px; color: #fda4af; font-size: 14px; }
        .hint { margin-top: 12px; color: var(--soft); font-size: 12px; }
    </style>
</head>
<body class="page">
<main class="container">
    <div class="brand">
        <strong>Baba PRO</strong>
        <span>Acesso seguro por telefone, codigo do baba e PIN</span>
    </div>

    <form method="post" action="/login.php" class="login-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="field">
            <label for="phone">Telefone</label>
            <input id="phone" name="phone" inputmode="numeric" autocomplete="tel" required placeholder="71997087082">
        </div>
        <div class="field">
            <label for="baba_code">Codigo do Baba</label>
            <input id="baba_code" name="baba_code" maxlength="20" required placeholder="BABA10">
        </div>
        <div class="field">
            <label for="pin">PIN (4 digitos)</label>
            <input id="pin" name="pin" inputmode="numeric" maxlength="4" required placeholder="1234">
        </div>
        <button class="btn btn-primary" type="submit">Entrar</button>
    </form>
    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p class="hint">Use o codigo do seu grupo para entrar no tenant correto.</p>
</main>
</body>
</html>
