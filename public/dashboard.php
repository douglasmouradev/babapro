<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\MatchRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;

$db = Database::connection();
$userRepo = new UserRepository($db);
$auth = new AuthService($userRepo);
$matches = new MatchRepository($db);

if (!$auth->isAuthenticated()) {
    redirect('/login.php');
}

if ($auth->needsBabaWelcome()) {
    redirect('/baba-bemvindo.php');
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

$userName = (string) $user['user_name'];
$nameParts = preg_split('/\s+/', trim($userName), 2);
$firstName = $nameParts[0] !== '' ? $nameParts[0] : 'Atleta';
$initials = user_initials($userName);
$memberFaces = $userRepo->listBabaMemberFaces((int) $user['baba_id']);
$rosterStartIndex = 0;
foreach ($memberFaces as $idx => $m) {
    if ((int) $m['id'] === (int) $user['user_id']) {
        $rosterStartIndex = (int) $idx;
        break;
    }
}
$myPhoto = isset($user['photo_path']) && is_string($user['photo_path']) && $user['photo_path'] !== ''
    ? $user['photo_path']
    : null;

$totalAttendance = $presenceSummary['confirmed'] + $presenceSummary['out'] + $presenceSummary['pending'];
$heroDenom = max($totalAttendance, 10);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Inicio</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page page--dashboard">
<main class="app app--dashboard">
    <header class="dash-topbar">
        <a href="/dashboard.php" class="dash-topbar-logo-wrap">
            <img src="/logo.jpg" width="56" height="56" alt="Baba PRO" class="dash-topbar-logo" decoding="async">
        </a>
        <div class="dash-topbar-right">
            <div class="dash-points" title="Pontos fantasy (em breve)">
                <span class="dash-points-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L15 8L22 9L17 14L18 21L12 18L6 21L7 14L2 9L9 8L12 2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                </span>
                <span class="dash-points-val">0</span>
                <span class="dash-points-label">PONTOS</span>
            </div>
            <?php if ($myPhoto !== null): ?>
                <a href="#perfil" class="dash-avatar dash-avatar--photo" aria-label="Perfil">
                    <img src="<?= htmlspecialchars($myPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40" decoding="async">
                </a>
            <?php else: ?>
                <a href="#perfil" class="dash-avatar" aria-label="Perfil"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
            <button type="button" id="dash-drawer-open" class="dash-burger-btn" aria-label="Menu" aria-controls="dash-drawer" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>

    <section class="dash-greet" aria-labelledby="dash-greet-title">
        <h1 id="dash-greet-title" class="dash-greet-title">Fala, <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>! <span class="dash-greet-emoji" aria-hidden="true">👊</span></h1>
        <p class="dash-greet-sub">Bora para mais um baba?</p>
    </section>

    <?php if ($nextMatch !== null): ?>
        <?php
        $starts = strtotime((string) $nextMatch['starts_at']);
        $dateStr = $starts !== false ? date('d/m/Y', $starts) : '';
        $timeStr = $starts !== false ? date('H:i', $starts) : '';
        $loc = trim((string) ($nextMatch['location'] ?? ''));
        ?>
        <section class="hero-match" aria-labelledby="hero-match-title">
            <div class="hero-match-bg" aria-hidden="true"></div>
            <div class="hero-match-inner">
                <div class="hero-match-top">
                    <span class="hero-eyebrow">Proximo jogo</span>
                    <span class="hero-badge"><?= (int) $presenceSummary['confirmed'] ?> / <?= (int) $heroDenom ?> confirmados</span>
                </div>
                <h2 id="hero-match-title" class="hero-title"><?= htmlspecialchars((string) $nextMatch['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <ul class="hero-meta">
                    <li><span class="hero-meta-ic" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span><?= htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') ?></li>
                    <li><span class="hero-meta-ic" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/></svg>
                    </span><?= htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php if ($loc !== ''): ?>
                        <li><span class="hero-meta-ic" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.35 7-10a7 7 0 10-14 0c0 5.65 7 10 7 10z" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="11" r="2.5" stroke="currentColor" stroke-width="1.5"/></svg>
                        </span><?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endif; ?>
                </ul>
                <?php if ($memberFaces !== []): ?>
                    <div class="hero-roster" data-hero-roster data-start-index="<?= (int) $rosterStartIndex ?>" tabindex="0" aria-label="Galera do baba">
                        <p class="hero-roster-kicker">Escalacao confirmada</p>
                        <div class="hero-roster-row">
                            <button type="button" class="hero-roster-nav hero-roster-nav--prev" data-hero-roster-prev aria-label="Anterior">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <div class="hero-roster-viewport">
                                <div class="hero-roster-track">
                                    <?php foreach ($memberFaces as $member): ?>
                                        <?php
                                        $mid = (int) $member['id'];
                                        $isMe = $mid === (int) $user['user_id'];
                                        $mPath = isset($member['photo_path']) && is_string($member['photo_path']) && $member['photo_path'] !== ''
                                            ? $member['photo_path']
                                            : null;
                                        ?>
                                        <div class="hero-roster-cell">
                                            <div class="hero-avatar-slot hero-avatar-slot--hero<?= $isMe ? ' hero-avatar-slot--me' : '' ?>">
                                                <?php if ($mPath !== null): ?>
                                                    <img class="hero-avatar-img" src="<?= htmlspecialchars($mPath, ENT_QUOTES, 'UTF-8') ?>" alt="" width="56" height="56" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <span class="hero-avatar-fallback"><?= htmlspecialchars(user_initials((string) $member['full_name']), ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="button" class="hero-roster-nav hero-roster-nav--next" data-hero-roster-next aria-label="Proximo">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <p class="hero-cta-line">Sua presenca faz a diferenca!</p>
                <form class="hero-presence" method="post" action="/dashboard.php">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-hero-go" type="submit" name="presence_action" value="confirmed"><span class="btn-hero-ic" aria-hidden="true">&#10003;</span> Vou jogar</button>
                    <button class="btn btn-hero-out" type="submit" name="presence_action" value="out"><span class="btn-hero-ic" aria-hidden="true">&#10007;</span> To fora</button>
                </form>
                <?php if ($feedback !== null): ?>
                    <p class="hero-feedback status-badge"><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($error !== null): ?>
                    <p class="hero-feedback status-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-ic" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10 0a4 4 0 10-8 0 4 4 0 008 0zm-4 10v-2a4 4 0 00-4-4H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </span>
                        <span class="hero-stat-n"><?= (int) $presenceSummary['confirmed'] ?></span>
                        <span class="hero-stat-l">Confirmados</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-ic" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5"/></svg>
                        </span>
                        <span class="hero-stat-n"><?= (int) $presenceSummary['pending'] ?></span>
                        <span class="hero-stat-l">Pendentes</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-ic hero-stat-ic--out" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </span>
                        <span class="hero-stat-n"><?= (int) $presenceSummary['out'] ?></span>
                        <span class="hero-stat-l">Fora</span>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="hero-match hero-match--empty">
            <div class="hero-match-bg" aria-hidden="true"></div>
            <div class="hero-match-inner">
                <span class="hero-eyebrow">Agenda</span>
                <h2 class="hero-title">Nenhum jogo agendado</h2>
                <p class="hero-empty-text">Quando o organizador criar o proximo jogo, ele aparece aqui com confirmacao de presenca.</p>
                <?php if ($error !== null): ?>
                    <p class="status-error hero-feedback"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel panel--flush dash-game-center">
        <p class="section-title">Centro</p>
        <h2 class="section-heading">Game Center</h2>
        <div class="module-grid module-grid--tiles module-grid--six">
            <a class="module module-tile m-purple" href="/sorteio.php">
                <span class="module-icon module-icon--shirt" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Fantasy League</span>
                    <span class="module-hint">Seu time e ligas</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
            <a class="module module-tile m-green" href="/mercado.php">
                <span class="module-icon module-icon--wallet" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Financeiro</span>
                    <span class="module-hint">Mercado e carteira</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
            <a class="module module-tile m-gold" href="/sorteio.php">
                <span class="module-icon module-icon--trophy" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Rankings</span>
                    <span class="module-hint">Hall da fama / Modo Campeonato</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
            <a class="module module-tile m-blue" href="/raio-x.php">
                <span class="module-icon module-icon--radar" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Raio X</span>
                    <span class="module-hint">Evolucao, historico e dados</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
            <a class="module module-tile m-orange" href="/resenhas.php">
                <span class="module-icon module-icon--chat" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Resenhas</span>
                    <span class="module-hint">Comunidade do baba</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
            <a class="module module-tile m-teal" href="/minha-regiao.php">
                <span class="module-icon module-icon--map" aria-hidden="true"></span>
                <span class="module-inner">
                    <span class="module-name">Minha regiao</span>
                    <span class="module-hint">Babas e quadras perto</span>
                </span>
                <span class="module-arrow" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </span>
            </a>
        </div>
    </section>

    <section class="spotlight" aria-labelledby="spotlight-title">
        <div class="spotlight-icon-wrap" id="spotlight-title" aria-hidden="true"><span class="spotlight-icon">🏆</span></div>
        <div class="spotlight-body">
            <p class="spotlight-kicker">Destaque da semana</p>
            <p class="spotlight-text">O MVP do ultimo jogo pode ser seu no proximo ranking.</p>
        </div>
        <a class="btn btn-spotlight" href="/sorteio.php">Ver rankings</a>
    </section>

    <section class="panel" id="perfil">
        <p class="section-title">Conta</p>
        <h2 class="section-heading">Perfil</h2>
        <p class="meta"><strong class="meta-lead"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p class="meta"><?= htmlspecialchars((string) $user['baba_name'], ENT_QUOTES, 'UTF-8') ?> &middot; codigo <?= htmlspecialchars((string) $user['baba_code'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="link-row link-row--spaced">
            <a href="/logout.php">Sair da conta</a>
            <?php if ($canManageUsers): ?>
                <span class="sep" aria-hidden="true">|</span>
                <a href="/usuarios.php" class="link-muted">Gerenciar usuarios</a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
$navActive = 'home';
$navCanManageUsers = $canManageUsers;
require __DIR__ . '/partials/bottom-nav.php';
?>
<script>
(() => {
    const root = document.querySelector('[data-hero-roster]');
    if (!root) return;
    const track = root.querySelector('.hero-roster-track');
    const slots = root.querySelectorAll('.hero-roster-cell .hero-avatar-slot');
    const prevBtn = root.querySelector('[data-hero-roster-prev]');
    const nextBtn = root.querySelector('[data-hero-roster-next]');
    const n = slots.length;
    if (!track || n === 0) return;

    let index = Math.min(Math.max(parseInt(root.dataset.startIndex || '0', 10) || 0, 0), n - 1);

    function wrap(i) {
        return ((i % n) + n) % n;
    }

    function setFocusVisual(activeIdx) {
        slots.forEach((el, j) => {
            const on = j === activeIdx;
            el.classList.toggle('hero-avatar-slot--focus', on);
            el.classList.toggle('hero-avatar-slot--dim', !on);
        });
    }

    function goToIndex(targetIndex, smooth) {
        index = wrap(targetIndex);
        const cell = slots[index].closest('.hero-roster-cell');
        if (cell) {
            cell.scrollIntoView({
                behavior: smooth ? 'smooth' : 'auto',
                inline: 'center',
                block: 'nearest',
            });
        }
        setFocusVisual(index);
    }

    prevBtn?.addEventListener('click', () => goToIndex(index - 1, true));
    nextBtn?.addEventListener('click', () => goToIndex(index + 1, true));

    slots.forEach((el, j) => {
        el.addEventListener('click', () => goToIndex(j, true));
    });

    root.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            goToIndex(index - 1, true);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            goToIndex(index + 1, true);
        }
    });

    requestAnimationFrame(() => goToIndex(index, false));
})();
</script>
<?php
$drawerUser = $user;
$drawerCanManageUsers = $canManageUsers;
$drawerActive = 'home';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
