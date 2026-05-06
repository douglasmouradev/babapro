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
    <title>Baba PRO - Mercado</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Mercado</h1>
    <p class="meta">Contratacoes e carteira do seu baba.</p>
    <div class="wallet">Saldo atual: R$ 50,00</div>
    <article class="item"><strong>Anderson Daronco</strong><br><span class="meta">Juiz | Cache por jogo: R$ 60,00</span></article>
    <article class="item"><strong>Atleta Free Agent</strong><br><span class="meta">OVR 81 | Passe: R$ 120,00</span></article>
</section>
<nav class="nav" aria-label="Navegacao principal">
    <a href="/dashboard.php">Home</a>
    <a href="/calendario.php">Calendario</a>
    <a href="/sorteio.php">Sorteio</a>
    <a class="active" href="/mercado.php">Mercado</a>
    <?php if ($canManageUsers): ?><a href="/usuarios.php">Usuarios</a><?php endif; ?>
</nav>
<?php
$drawerUser = $user;
$drawerCanManageUsers = $canManageUsers;
$drawerActive = 'mercado';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
