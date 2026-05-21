<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\BabaRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

$db = Database::connection();
$auth = new AuthService(new UserRepository($db));

if (!$auth->isAuthenticated()) {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf'] ?? null)) {
        redirect('/baba-bemvindo.php');
    }
    $auth->markBabaWelcomeSeen();
    redirect('/dashboard.php');
}

if (!$auth->needsBabaWelcome()) {
    redirect('/dashboard.php');
}

$user = $auth->user();
$baba = (new BabaRepository($db))->findById((int) $user['baba_id']);

$babaName = (string) ($baba['name'] ?? $user['baba_name'] ?? 'Seu Baba');
$babaCode = strtoupper((string) ($baba['code'] ?? $user['baba_code'] ?? ''));
$babaPhoto = null;
if (isset($baba['photo_path']) && is_string($baba['photo_path']) && $baba['photo_path'] !== '') {
    $babaPhoto = $baba['photo_path'];
} elseif (isset($user['baba_photo_path']) && is_string($user['baba_photo_path']) && $user['baba_photo_path'] !== '') {
    $babaPhoto = $user['baba_photo_path'];
}

$welcomeDefaults = [
    'BABA10' => 'Fala, time! O Baba de Quinta esta no ar. Bora aquecer, confirmar presenca e fazer acontecer mais um jogo epico. Boas vendas na quadra!',
];

$welcomeMessage = null;
if (isset($baba['welcome_message']) && is_string($baba['welcome_message']) && trim($baba['welcome_message']) !== '') {
    $welcomeMessage = trim($baba['welcome_message']);
} elseif (isset($user['baba_welcome_message']) && is_string($user['baba_welcome_message']) && $user['baba_welcome_message'] !== '') {
    $welcomeMessage = $user['baba_welcome_message'];
} elseif (isset($welcomeDefaults[$babaCode])) {
    $welcomeMessage = $welcomeDefaults[$babaCode];
} else {
    $welcomeMessage = sprintf(
        'Bem-vindo ao %s! Bora para mais um baba com energia e boas vendas.',
        $babaName
    );
}

$babaInitials = user_initials($babaName);
$logoUrl = asset_url('/logo.jpg');
$cssUrl = asset_url('/assets/app.css');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - <?= htmlspecialchars($babaName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/jpeg" href="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') ?>">
    <style>
        body.page--baba-welcome {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100dvh;
            padding: 24px 16px 32px;
            margin: 0;
            background-color: #060912;
            background-image:
                radial-gradient(ellipse 120% 80% at 50% -20%, rgba(232, 163, 23, 0.09) 0%, transparent 55%),
                linear-gradient(180deg, #060912 0%, #04060c 100%);
            color: #f1f4fb;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
        }
        .baba-welcome {
            width: min(420px, 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .baba-welcome-app-logo {
            width: min(120px, 32vw);
            height: auto;
            border-radius: 16px;
            filter: drop-shadow(0 10px 28px rgba(0, 0, 0, 0.45));
        }
        .baba-welcome-card {
            width: 100%;
            padding: 28px 22px 24px;
            border-radius: 18px;
            border: 1px solid rgba(56, 74, 108, 0.55);
            background: linear-gradient(168deg, rgba(20, 28, 48, 0.97) 0%, rgba(6, 9, 18, 0.98) 100%);
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
            text-align: center;
        }
        .baba-welcome-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(232, 163, 23, 0.55);
            box-shadow: 0 0 32px rgba(232, 163, 23, 0.22);
            margin: 0 auto 12px;
        }
        .baba-welcome-photo--fallback {
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 800;
            color: #0a0e16;
            background: linear-gradient(145deg, #ffe08a 0%, #e8a317 100%);
        }
        .baba-welcome-code {
            margin: 0 0 6px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #fcc424;
        }
        .baba-welcome-title {
            margin: 0 0 14px;
            font-size: 1.55rem;
            font-weight: 800;
        }
        .baba-welcome-message {
            margin: 0 0 22px;
            font-size: 0.95rem;
            line-height: 1.55;
            color: #8b9bb8;
        }
        .btn-baba-enter {
            width: 100%;
            font-size: 1rem;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
            background: linear-gradient(180deg, #ffd049 0%, #e8a317 52%, #b87400 100%);
            color: #140e05;
            box-shadow: 0 8px 28px rgba(232, 163, 23, 0.35);
        }
    </style>
</head>
<body class="page page--baba-welcome">
<main class="baba-welcome">
    <div class="baba-welcome-brand">
        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" width="120" height="120" alt="Baba PRO" class="baba-welcome-app-logo" decoding="async">
    </div>

    <section class="baba-welcome-card" aria-labelledby="baba-welcome-title">
        <div class="baba-welcome-photo-wrap">
            <?php if ($babaPhoto !== null): ?>
                <img
                    class="baba-welcome-photo"
                    src="<?= htmlspecialchars($babaPhoto, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Foto do <?= htmlspecialchars($babaName, ENT_QUOTES, 'UTF-8') ?>"
                    width="120"
                    height="120"
                    decoding="async"
                >
            <?php else: ?>
                <div class="baba-welcome-photo baba-welcome-photo--fallback" aria-hidden="true">
                    <?= htmlspecialchars($babaInitials, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
        </div>

        <p class="baba-welcome-code"><?= htmlspecialchars($babaCode, ENT_QUOTES, 'UTF-8') ?></p>
        <h1 id="baba-welcome-title" class="baba-welcome-title"><?= htmlspecialchars($babaName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="baba-welcome-message"><?= htmlspecialchars($welcomeMessage, ENT_QUOTES, 'UTF-8') ?></p>

        <form method="post" action="/baba-bemvindo.php" class="baba-welcome-actions">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary btn-baba-enter">Entrar no baba</button>
        </form>
    </section>
</main>
</body>
</html>
