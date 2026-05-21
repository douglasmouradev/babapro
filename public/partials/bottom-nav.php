<?php

declare(strict_types=1);

/** @var string $navActive home|calendario|sorteio|mercado|usuario */
/** @var bool $navCanManageUsers */

$navActive = $navActive ?? 'home';
$navCanManageUsers = $navCanManageUsers ?? false;

$userHref = $navCanManageUsers ? '/usuarios.php' : '/dashboard.php#perfil';
$userActive = $navActive === 'usuario';

?>
<nav class="nav nav--dash" aria-label="Navegacao principal">
    <a class="nav-item<?= $navActive === 'home' ? ' active' : '' ?>" href="/dashboard.php">
        <span class="nav-ic" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 01-1 1h-5v-6H10v6H5a1 1 0 01-1-1v-9.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        </span>
        Inicio
    </a>
    <a class="nav-item<?= $navActive === 'calendario' ? ' active' : '' ?>" href="/calendario.php">
        <span class="nav-ic" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.5"/></svg>
        </span>
        Calendario
    </a>
    <a class="nav-item<?= $navActive === 'sorteio' ? ' active' : '' ?>" href="/sorteio.php">
        <span class="nav-ic" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10 0a4 4 0 10-8 0 4 4 0 008 0zm-4 10v-2a4 4 0 00-4-4H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </span>
        Sorteio
    </a>
    <a class="nav-item<?= $navActive === 'mercado' ? ' active' : '' ?>" href="/mercado.php">
        <span class="nav-ic" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 8h15l-1.5 9h-12zM6 8L5 3H2M9 20a1 1 0 102 0 1 1 0 00-2 0zm8 0a1 1 0 102 0 1 1 0 00-2 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </span>
        Mercado
    </a>
    <a class="nav-item<?= $userActive ? ' active' : '' ?>" href="<?= htmlspecialchars($userHref, ENT_QUOTES, 'UTF-8') ?>">
        <span class="nav-ic" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" stroke="currentColor" stroke-width="1.5"/></svg>
        </span>
        Usuario
    </a>
</nav>
