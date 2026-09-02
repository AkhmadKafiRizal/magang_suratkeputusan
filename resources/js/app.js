const dashboardShell = document.querySelector('[data-dashboard-shell]');

if (dashboardShell) {
    const openButton = dashboardShell.querySelector('[data-sidebar-open]');
    const closeButton = dashboardShell.querySelector('[data-sidebar-close]');
    const sidebarLinks = dashboardShell.querySelectorAll('.sidebar-link');

    const setSidebarOpen = (isOpen) => {
        dashboardShell.classList.toggle('sidebar-is-open', isOpen);
        openButton?.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    openButton?.addEventListener('click', () => setSidebarOpen(true));
    closeButton?.addEventListener('click', () => setSidebarOpen(false));

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 980) {
                setSidebarOpen(false);
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 980) {
            setSidebarOpen(false);
        }
    });
}
