(() => {
    const root = document.getElementById('dash-drawer');
    const openBtn = document.getElementById('dash-drawer-open');
    if (!root || !openBtn) {
        return;
    }

    const scrim = root.querySelector('.dash-drawer-scrim');
    const closeBtn = root.querySelector('[data-drawer-close]');

    function openDrawer() {
        root.classList.add('is-open');
        root.removeAttribute('aria-hidden');
        document.body.classList.add('drawer-open');
        openBtn.setAttribute('aria-expanded', 'true');
        closeBtn?.focus({ preventScroll: true });
    }

    function closeDrawer() {
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('drawer-open');
        openBtn.setAttribute('aria-expanded', 'false');
        openBtn.focus({ preventScroll: true });
    }

    openBtn.addEventListener('click', () => {
        if (root.classList.contains('is-open')) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    scrim?.addEventListener('click', closeDrawer);
    closeBtn?.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && root.classList.contains('is-open')) {
            e.preventDefault();
            closeDrawer();
        }
    });

    root.querySelectorAll('.dash-drawer-sheet a[href]').forEach((a) => {
        a.addEventListener('click', () => closeDrawer());
    });
})();
