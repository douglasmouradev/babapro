<?php

declare(strict_types=1);

/** @var array $drawerUser */
/** @var bool $drawerCanManageUsers */
/** @var string $drawerActive home|calendario|sorteio|mercado|usuarios */

$babaLabel = htmlspecialchars((string) ($drawerUser['baba_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$active = $drawerActive;

?>
<div id="dash-drawer" class="dash-drawer" aria-hidden="true">
    <div class="dash-drawer-scrim" aria-hidden="true"></div>
    <aside class="dash-drawer-sheet" role="dialog" aria-modal="true" aria-labelledby="dash-drawer-brand">
        <div class="dash-drawer-header">
            <img src="/logo.jpg" width="48" height="48" alt="" class="dash-drawer-logo" decoding="async">
            <div class="dash-drawer-head-text">
                <div id="dash-drawer-brand" class="dash-drawer-brand">Baba PRO</div>
                <div class="dash-drawer-baba"><?= $babaLabel !== '' ? $babaLabel : 'Seu grupo' ?></div>
            </div>
            <button type="button" class="dash-drawer-close" data-drawer-close aria-label="Fechar menu">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
        </div>
        <nav class="dash-drawer-nav" aria-label="Menu lateral">
            <a class="dash-drawer-link<?= $active === 'home' ? ' is-active' : '' ?>" href="/dashboard.php">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 01-1 1h-5v-6H10v6H5a1 1 0 01-1-1v-9.5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                </span>
                <span class="dash-drawer-label">Início</span>
            </a>
            <a class="dash-drawer-link<?= $active === 'calendario' ? ' is-active' : '' ?>" href="/calendario.php">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.5"/></svg>
                </span>
                <span class="dash-drawer-label">Meus jogos</span>
            </a>
            <a class="dash-drawer-link<?= $active === 'sorteio' ? ' is-active' : '' ?>" href="/sorteio.php">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M8 8h8M8 12h8M8 16h5M6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z" stroke="currentColor" stroke-width="1.5"/></svg>
                </span>
                <span class="dash-drawer-label">Fantasy / Sorteio</span>
            </a>
            <a class="dash-drawer-link<?= $active === 'mercado' ? ' is-active' : '' ?>" href="/mercado.php">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M6 8h15l-1.5 9h-12zM6 8L5 3H2M9 20a1 1 0 102 0 1 1 0 00-2 0zm8 0a1 1 0 102 0 1 1 0 00-2 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </span>
                <span class="dash-drawer-label">Mercado</span>
            </a>
            <div class="dash-drawer-divider" role="presentation"></div>
            <a class="dash-drawer-link" href="/dashboard.php#perfil">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z" stroke="currentColor" stroke-width="1.5"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001.51 1 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" stroke="currentColor" stroke-width="1.5"/></svg>
                </span>
                <span class="dash-drawer-label">Configurações</span>
            </a>
            <?php if ($drawerCanManageUsers): ?>
                <a class="dash-drawer-link<?= $active === 'usuarios' ? ' is-active' : '' ?>" href="/usuarios.php">
                    <span class="dash-drawer-ic" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm14 4v2M16 3.13a4 4 0 010 7.75M21 21v-2a4 4 0 00-3-3.87" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <span class="dash-drawer-label">Usuários</span>
                </a>
            <?php endif; ?>
            <a class="dash-drawer-link dash-drawer-link--danger" href="/logout.php">
                <span class="dash-drawer-ic" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span class="dash-drawer-label">Sair</span>
            </a>
        </nav>
    </aside>
</div>
<script src="/assets/app-drawer.js" defer></script>
