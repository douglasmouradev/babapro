<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;

$auth = new AuthService(new UserRepository(Database::connection()));

if (!$auth->isAuthenticated()) {
    redirect('/login.php');
}

$user = $auth->user();
$canManageUsers = $auth->canManageUsers();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Sorteio</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Sorteio</h1>
    <p class="meta">Gere confronto equilibrado por overall e posicao.</p>
    <div class="row">
        <article class="card"><strong>Time A</strong><br><span class="meta">OVR medio 78</span></article>
        <article class="card"><strong>Time B</strong><br><span class="meta">OVR medio 77</span></article>
    </div>
    <button class="btn btn-primary" style="margin-top:12px;width:100%;" type="button">Gerar confronto equilibrado</button>
</section>
<nav class="nav" aria-label="Navegacao principal">
    <a href="/dashboard.php">Home</a>
    <a href="/calendario.php">Calendario</a>
    <a class="active" href="/sorteio.php">Sorteio</a>
    <a href="/mercado.php">Mercado</a>
    <?php if ($canManageUsers): ?><a href="/usuarios.php">Usuarios</a><?php endif; ?>
</nav>
<?php
$drawerUser = $user;
$drawerCanManageUsers = $canManageUsers;
$drawerActive = 'sorteio';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
