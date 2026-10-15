<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#083150">
    <title><?= esc($title) ?> | Epic Admin</title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jura:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css') ?>">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
            <div class="admin-brand">
                <a href="<?= site_url('admin') ?>"><img src="<?= base_url('assets/images/epic-logo.png') ?>" alt="Epic Storage Solutions"><span>Admin Panel</span></a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Close navigation">&times;</button>
            </div>
            <nav class="admin-nav">
                <span class="admin-nav-label">Dashboard Panel</span>
                <a class="<?= $activeAdminPage === 'dashboard' ? 'active' : '' ?>" href="<?= site_url('admin') ?>"><span class="nav-icon" aria-hidden="true">⌂</span><span>Dashboard</span></a>
                <span class="admin-nav-label">Operations</span>
                <a class="<?= $activeAdminPage === 'bookings' ? 'active' : '' ?>" href="<?= site_url('admin/bookings') ?>"><span class="nav-icon" aria-hidden="true">▣</span><span>Bookings</span></a>
                <a class="<?= $activeAdminPage === 'schedule' ? 'active' : '' ?>" href="<?= site_url('admin/schedule') ?>"><span class="nav-icon" aria-hidden="true">◷</span><span>Calendar schedule</span></a>
                <a class="<?= $activeAdminPage === 'logistics' ? 'active' : '' ?>" href="<?= site_url('admin/logistics') ?>"><span class="nav-icon" aria-hidden="true">↔</span><span>Pickup &amp; drop-off</span></a>
                <a class="<?= $activeAdminPage === 'dorms' ? 'active' : '' ?>" href="<?= site_url('admin/dorms') ?>"><span class="nav-icon" aria-hidden="true">◫</span><span>Dorms</span></a>
                <a class="<?= $activeAdminPage === 'items' ? 'active' : '' ?>" href="<?= site_url('admin/items') ?>"><span class="nav-icon" aria-hidden="true">◇</span><span>Items &amp; sizes</span></a>
                <span class="admin-nav-label">Finance</span>
                <a class="<?= $activeAdminPage === 'payments' ? 'active' : '' ?>" href="<?= site_url('admin/payments') ?>"><span class="nav-icon" aria-hidden="true">$</span><span>Payments</span></a>
                <a class="<?= $activeAdminPage === 'transactions' ? 'active' : '' ?>" href="<?= site_url('admin/transactions') ?>"><span class="nav-icon" aria-hidden="true">⇄</span><span>Transactions</span></a>
                <span class="admin-nav-label">Administration</span>
                <a class="<?= $activeAdminPage === 'users' ? 'active' : '' ?>" href="<?= site_url('admin/users') ?>"><span class="nav-icon" aria-hidden="true">◎</span><span>Users</span></a>
                <a class="<?= $activeAdminPage === 'settings' ? 'active' : '' ?>" href="<?= site_url('admin/settings') ?>"><span class="nav-icon" aria-hidden="true">⚙</span><span>Settings</span></a>
                <a class="<?= $activeAdminPage === 'account' ? 'active' : '' ?>" href="<?= site_url('admin/account') ?>"><span class="nav-icon" aria-hidden="true">○</span><span>Admin account</span></a>
            </nav>
            <a class="sidebar-site-link" href="<?= site_url('/') ?>" target="_blank" rel="noopener"><span aria-hidden="true">↗</span> View public website</a>
        </aside>
        <div class="sidebar-scrim" data-sidebar-close></div>

        <div class="admin-content">
            <header class="admin-topbar">
                <button class="sidebar-open" type="button" data-sidebar-open aria-label="Open navigation">☰</button>
                <div class="topbar-title"><span class="topbar-kicker">Epic administration</span><strong><?= esc($title) ?></strong></div>
                <div class="topbar-actions">
                    <a class="topbar-site" href="<?= site_url('/') ?>" target="_blank" rel="noopener">View website</a>
                    <a class="topbar-profile" href="<?= site_url('admin/account') ?>"><span class="admin-avatar"><?= esc(mb_strtoupper(mb_substr((string) session()->get('admin_user_name'), 0, 1))) ?></span><span><strong><?= esc((string) session()->get('admin_user_name')) ?></strong><small>Administrator</small></span></a>
                    <form action="<?= site_url('admin/logout') ?>" method="post" data-confirm="Are you sure you want to end your administrator session?" data-confirm-title="Log out of Epic Admin?" data-confirm-button="Yes, log out" data-loading-title="Signing you out">
                        <?= csrf_field() ?>
                        <button class="topbar-logout" type="submit" aria-label="Log out" title="Log out">↪</button>
                    </form>
                </div>
            </header>

            <main class="admin-main">
                <?= view('components/notification') ?>
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.3/js/dataTables.min.js"></script>
    <script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/notifications.js') ?>"></script>
    <script src="<?= base_url('assets/js/admin.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
