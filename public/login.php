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
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        body.page {
            position: relative;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        #matrix-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            opacity: 0.14;
            pointer-events: none;
        }

        .login-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            margin: 20px auto 32px;
        }

        .container {
            position: relative;
            border-radius: 22px;
            padding: 1px;
            background: linear-gradient(
                145deg,
                rgba(232, 163, 23, 0.45) 0%,
                rgba(56, 74, 108, 0.35) 45%,
                rgba(18, 26, 43, 0.6) 100%
            );
            box-shadow: 0 28px 72px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
        }

        .container-inner {
            border-radius: 21px;
            padding: 26px 24px 24px;
            background: linear-gradient(
                168deg,
                rgba(20, 28, 48, 0.97) 0%,
                rgba(6, 9, 18, 0.98) 100%
            );
            backdrop-filter: blur(20px);
        }

        .brand {
            margin-bottom: 22px;
            padding: 18px 16px 20px;
            border-radius: 16px;
            border: 1px solid rgba(56, 74, 108, 0.45);
            background: radial-gradient(
                ellipse 100% 120% at 50% 0%,
                rgba(232, 163, 23, 0.08) 0%,
                rgba(10, 14, 24, 0.65) 55%
            );
            text-align: center;
        }

        .brand-logo {
            display: block;
            max-width: min(196px, 70%);
            height: auto;
            margin: 0 auto 12px;
            filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.35));
        }

        .brand span {
            display: block;
            color: var(--soft);
            font-size: 13px;
            font-weight: 500;
            line-height: 1.45;
            max-width: 280px;
            margin: 0 auto;
        }

        .login-eyebrow {
            margin: 0 0 14px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .login-grid {
            display: grid;
            gap: 14px;
        }

        .login-grid .btn-primary {
            width: 100%;
            margin-top: 4px;
            font-size: 15px;
            letter-spacing: 0.02em;
        }

        .alert-error {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(239, 68, 68, 0.35);
            background: rgba(239, 68, 68, 0.1);
            color: #fecaca;
            font-size: 14px;
            line-height: 1.4;
        }

        .hint {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            border: 1px dashed rgba(56, 74, 108, 0.55);
            color: var(--soft);
            font-size: 13px;
            line-height: 1.45;
        }

        .legal {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(148, 165, 193, 0.12);
            color: var(--muted);
            font-size: 11px;
            line-height: 1.55;
            text-align: center;
        }

        .legal div + div {
            margin-top: 6px;
        }
    </style>
</head>
<body class="page">
<canvas id="matrix-bg" aria-hidden="true"></canvas>
<div class="login-shell">
<main class="container">
    <div class="container-inner">
        <div class="brand">
            <img class="brand-logo" src="/logo.jpg" width="200" alt="Baba PRO — Gestao, jogos, goleiros, arbitros" decoding="async">
            <span>Acesso seguro por telefone, codigo do baba e PIN de quatro digitos.</span>
        </div>

        <p class="login-eyebrow">Credenciais</p>
        <form method="post" action="/login.php" class="login-grid">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="field">
                <label for="phone">Telefone</label>
                <input id="phone" name="phone" inputmode="numeric" autocomplete="tel" placeholder="Ex.: 11999998888" required>
            </div>
            <div class="field">
                <label for="baba_code">Codigo do Baba</label>
                <input id="baba_code" name="baba_code" maxlength="20" placeholder="Codigo do seu grupo" required>
            </div>
            <div class="field">
                <label for="pin">PIN</label>
                <input id="pin" name="pin" inputmode="numeric" maxlength="4" placeholder="Quatro digitos" autocomplete="one-time-code" required>
            </div>
            <button class="btn btn-primary" type="submit">Entrar na conta</button>
        </form>
        <?php if ($error !== null): ?>
            <p class="alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p class="hint">Use o codigo do seu grupo para acessar o ambiente correto (multi-tenant).</p>
        <div class="legal">
            <div>&copy; <?= date('Y') ?> Baba PRO. Todos os direitos reservados.</div>
            <div>Dados tratados conforme a LGPD (Lei 13.709/2018).</div>
        </div>
    </div>
</main>
</div>
<script>
    (() => {
        const canvas = document.getElementById('matrix-bg');
        const ctx = canvas.getContext('2d');
        const charset = '01BABAPROFUTEBOL';
        const fontSize = 14;
        let columns = 0;
        let drops = [];

        const resize = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            columns = Math.floor(canvas.width / fontSize);
            drops = Array.from({ length: columns }, () => Math.floor(Math.random() * -40));
        };

        const draw = () => {
            ctx.fillStyle = 'rgba(5, 8, 15, 0.07)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = Math.random() > 0.55 ? 'rgba(200, 155, 45, 0.38)' : 'rgba(90, 118, 160, 0.28)';
            ctx.font = `${fontSize}px monospace`;

            for (let i = 0; i < drops.length; i += 1) {
                const char = charset.charAt(Math.floor(Math.random() * charset.length));
                const x = i * fontSize;
                const y = drops[i] * fontSize;
                ctx.fillText(char, x, y);

                if (y > canvas.height && Math.random() > 0.975) {
                    drops[i] = 0;
                }
                drops[i] += 1;
            }
        };

        resize();
        window.addEventListener('resize', resize);
        setInterval(draw, 52);
    })();
</script>
</body>
</html>
