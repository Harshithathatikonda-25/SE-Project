<aside class="sidebar dashboard-sidebar">
    <div class="brand">
        <div class="brand-icon" aria-hidden="true">⌁</div>
        <div><strong>GateFlow-<span>AI</span></strong><small>CAMPUS VEHICLE SECURITY</small></div>
        <button class="sidebar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="app-navigation">☰</button>
    </div>

    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <nav id="app-navigation" aria-label="Main navigation">
        <a href="dashboard.php#overview" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>><span>⌂</span>Dashboard</a>
        <a href="camera.php" class="<?= $currentPage === 'camera.php' ? 'active' : '' ?>"><span>▣</span>Live Monitoring</a>
        <a href="vehicles.php" class="<?= in_array($currentPage, ['vehicles.php', 'add_vehicle.php'], true) ? 'active' : '' ?>"><span>▰</span>Vehicles</a>
        <a href="entry_exit.php" class="<?= $currentPage === 'entry_exit.php' ? 'active' : '' ?>"><span>⇥</span>Entry / Exit</a>
        <a href="anpr.php" class="<?= $currentPage === 'anpr.php' ? 'active' : '' ?>"><span>⛶</span>ANPR</a>
        <a href="crowd_monitoring.php" class="<?= $currentPage === 'crowd_monitoring.php' ? 'active' : '' ?>"><span>♧</span>Crowd Monitoring</a>
        <a href="alerts.php" class="<?= $currentPage === 'alerts.php' ? 'active' : '' ?>"><span>♟</span>Alerts</a>
        <a href="reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>"><span>▥</span>Reports</a>
        <a href="users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>"><span>♙</span>Users</a>
        <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>"><span>⚙</span>Settings</a>
    </nav>
    <div class="sidebar-system"><span class="system-pulse"></span><span><strong>Database Online</strong><small>Camera &amp; gate integration pending</small></span></div>
</aside>
<script>
(() => {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    const resetModuleScroll = () => {
        if (!window.location.hash) window.scrollTo(0, 0);
    };
    window.addEventListener('pageshow', resetModuleScroll);

    const sidebar = document.querySelector('.dashboard-sidebar');
    const toggle = sidebar?.querySelector('.sidebar-toggle');
    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });

    sidebar.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', () => {
        sidebar.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation');
    }));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            sidebar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open navigation');
        }
    });
})();
</script>
