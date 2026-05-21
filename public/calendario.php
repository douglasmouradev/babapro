<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\MatchRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

$db = Database::connection();
$auth = new AuthService(new UserRepository($db));
$matches = new MatchRepository($db);

if (!$auth->isAuthenticated()) {
    redirect('/login.php');
}

if ($auth->needsBabaWelcome()) {
    redirect('/baba-bemvindo.php');
}

$canManageUsers = $auth->canManageUsers();
$me = $auth->user();
$error = null;
$feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManageUsers) {
    if (!verifyCsrfToken($_POST['_csrf'] ?? null)) {
        $error = 'Sessao expirada. Atualize a pagina.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $startsAt = trim($_POST['starts_at'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if ($title === '' || $startsAt === '') {
            $error = 'Preencha titulo e data/hora do jogo.';
        } else {
            $timestamp = strtotime($startsAt);
            if ($timestamp === false) {
                $error = 'Data/hora invalida.';
            } else {
                $matchDate = date('Y-m-d H:i:s', $timestamp);
                $matches->create(
                    (int) $me['baba_id'],
                    (int) $me['user_id'],
                    $title,
                    $matchDate,
                    $location
                );
                $feedback = 'Jogo criado com sucesso.';
            }
        }
    }
}

$matchList = $matches->listByBaba((int) $me['baba_id']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Calendario</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Calendario</h1>
    <p class="meta">Agenda de jogos e eventos do baba.</p>
    <?php if ($canManageUsers): ?>
        <form method="post" action="/calendario.php">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="title">Titulo do jogo</label>
                    <input id="title" name="title" required>
                </div>
                <div class="field">
                    <label for="starts_at">Data e horario</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" required>
                </div>
                <div class="field">
                    <label for="location">Local</label>
                    <input id="location" name="location">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Criar jogo</button>
        </form>
        <?php if ($feedback !== null): ?><p class="message-ok"><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <?php if ($error !== null): ?><p class="message-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php endif; ?>

    <?php foreach ($matchList as $match): ?>
        <article class="match">
            <strong><?= htmlspecialchars((string) $match['title'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            <span class="meta">
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $match['starts_at'])), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($match['location'])): ?> | <?= htmlspecialchars((string) $match['location'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                | <?= htmlspecialchars((string) $match['status'], ENT_QUOTES, 'UTF-8') ?>
            </span>
        </article>
    <?php endforeach; ?>
    <?php if ($matchList === []): ?><p class="meta">Nenhum jogo cadastrado.</p><?php endif; ?>
</section>
<?php
$navActive = 'calendario';
$navCanManageUsers = $canManageUsers;
require __DIR__ . '/partials/bottom-nav.php';
?>
<?php
$drawerUser = $me;
$drawerCanManageUsers = $canManageUsers;
$drawerActive = 'calendario';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
