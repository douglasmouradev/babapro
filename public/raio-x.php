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
    <title>Baba PRO - Raio X</title>
    <link rel="icon" type="image/jpeg" href="/logo.jpg">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="page">
<?php require __DIR__ . '/partials/dash-topbar-drawer.php'; ?>
<section class="panel app">
    <h1>Raio X</h1>
    <p class="meta">Evolucao, historico e dados do seu desempenho no baba.</p>
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
