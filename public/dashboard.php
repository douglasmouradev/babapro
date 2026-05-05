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

$user = $auth->user();
$canManageUsers = $auth->canManageUsers();
$nextMatch = $matches->findNextByBaba((int) $user['baba_id']);
$feedback = null;
$error = null;
$presenceSummary = ['confirmed' => 0, 'out' => 0, 'pending' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['_csrf'] ?? null)) {
        $error = 'Sessao expirada. Atualize a pagina.';
    } else {
    $action = $_POST['presence_action'] ?? '';
        if ($nextMatch === null) {
            $error = 'Nao existe jogo agendado para confirmar presenca.';
        } elseif (in_array($action, ['confirmed', 'out'], true)) {
            $ok = $matches->upsertAttendanceForUser(
                (int) $nextMatch['id'],
                (int) $user['baba_id'],
                (int) $user['user_id'],
                $action
            );
            if ($ok) {
                $feedback = $action === 'confirmed' ? 'Presenca confirmada.' : 'Voce marcou como fora.';
            } else {
                $error = 'Usuario sem atleta vinculado no baba.';
            }
        }
    }
}

if ($nextMatch !== null) {
    $presenceSummary = $matches->attendanceSummary((int) $nextMatch['id']);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Dashboard</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<main class="app">
    <section class="panel">
        <h1 class="title">Entrou no <?= htmlspecialchars((string) $user['baba_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="meta">
            Usuario: <?= htmlspecialchars((string) $user['user_name'], ENT_QUOTES, 'UTF-8') ?>
            | Codigo: <?= htmlspecialchars((string) $user['baba_code'], ENT_QUOTES, 'UTF-8') ?>
            | Perfil: <?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php if ($nextMatch !== null): ?>
            <p class="meta">
                Proximo jogo: <?= htmlspecialchars((string) $nextMatch['title'], ENT_QUOTES, 'UTF-8') ?>
                em <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $nextMatch['starts_at'])), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($nextMatch['location'])): ?>
                    | <?= htmlspecialchars((string) $nextMatch['location'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <form class="presence" method="post" action="/dashboard.php">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-yes" type="submit" name="presence_action" value="confirmed">Vou Jogar</button>
            <button class="btn btn-no" type="submit" name="presence_action" value="out">To Fora</button>
        </form>
        <?php if ($feedback !== null): ?>
            <p class="status-badge">
                <?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <p class="status-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($nextMatch !== null): ?>
            <p class="meta">
                Confirmados: <?= $presenceSummary['confirmed'] ?>
                | Fora: <?= $presenceSummary['out'] ?>
                | Pendentes: <?= $presenceSummary['pending'] ?>
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2 class="meta">Game Center</h2>
        <div class="module-grid">
            <a class="module m-blue" href="/sorteio.php">Fantasy League</a>
            <a class="module m-green" href="/mercado.php">Financeiro</a>
            <a class="module m-orange" href="/calendario.php">Rankings</a>
            <a class="module m-purple" href="/mercado.php">Raio X</a>
        </div>
    </section>

    <section class="panel">
        <h2 class="meta">Escalar atletas</h2>
        <div class="lineup">
            <article class="player">
                <div>
                    <strong>Neymar Jr</strong><br>
                    <span>ATA | OVR 89</span>
                </div>
                <span class="tag">OK</span>
            </article>
            <article class="player">
                <div>
                    <strong>Casemiro</strong><br>
                    <span>VOL | OVR 85</span>
                </div>
                <span class="tag">OK</span>
            </article>
            <article class="player">
                <div>
                    <strong>Alisson</strong><br>
                    <span>GOL | OVR 87</span>
                </div>
                <span class="tag">OK</span>
            </article>
        </div>
    </section>

    <section class="panel">
        <a href="/logout.php" style="color:#ffcb46;text-decoration:none;font-weight:700;">Sair da conta</a>
        <?php if ($canManageUsers): ?>
            <span style="margin-left:12px;color:#8ab7ff;">|</span>
            <a href="/usuarios.php" style="color:#8ab7ff;text-decoration:none;font-weight:700;margin-left:12px;">Gerenciar usuarios</a>
        <?php endif; ?>
    </section>
</main>

<nav class="nav" aria-label="Navegacao principal">
    <a class="active" href="/dashboard.php">Home</a>
    <a href="/calendario.php">Calendario</a>
    <a href="/sorteio.php">Sorteio</a>
    <a href="/mercado.php">Mercado</a>
    <?php if ($canManageUsers): ?>
        <a href="/usuarios.php">Usuarios</a>
    <?php endif; ?>
</nav>
</body>
</html>
