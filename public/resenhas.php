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

if ($auth->needsBabaWelcome()) {
    redirect('/baba-bemvindo.php');
}

$user = $auth->user();
$canManageUsers = $auth->canManageUsers();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Baba PRO - Resenhas</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Resenhas</h1>
    <p class="meta">Comunidade e resenha do seu baba.</p>
    <p class="meta">Modulo em desenvolvimento — em breve.</p>
</section>
<?php
$navActive = 'home';
$navCanManageUsers = $canManageUsers;
require __DIR__ . '/partials/bottom-nav.php';
$drawerUser = $user;
$drawerCanManageUsers = $canManageUsers;
$drawerActive = 'home';
require __DIR__ . '/partials/app-drawer.php';
?>
</body>
</html>
