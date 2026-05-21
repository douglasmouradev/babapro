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
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - <?= htmlspecialchars($babaName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page page--baba-welcome">
<main class="baba-welcome">
    <div class="baba-welcome-brand">
        <img src="/logo.jpg" width="120" height="120" alt="Baba PRO" class="baba-welcome-app-logo" decoding="async">
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
