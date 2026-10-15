<?php
$currentPath        = uri_string();
$isBookingDetail    = (bool) preg_match('#^account/bookings/\d+#', $currentPath);
$activeDashboard    = $currentPath === 'account';
$activeMyBookings   = $currentPath === 'account/bookings' || $isBookingDetail;
$activeNewBooking   = str_starts_with($currentPath, 'account/bookings/new');
$activePayments     = $currentPath === 'account/payments';
$activePickupDates  = $currentPath === 'account/pickup-dates';
$activeDropoffDates = $currentPath === 'account/dropoff-dates';
$activePayHistory   = $currentPath === 'account/payment-history';
$activeBalance      = $currentPath === 'account/balance';
$activeProfile      = $currentPath === 'account/profile';
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#083150">
    <title><?= esc($title) ?></title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jura:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/admin.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/account.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/account.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body class="admin-body customer-body">
    <div class="admin-shell">
        <aside class="admin-sidebar" id="adminSidebar" aria-label="Account navigation">
            <div class="admin-brand">
                <a href="<?= site_url('account') ?>"><img src="<?= base_url('assets/images/epic-logo.png') ?>" alt="Epic Storage Solutions"><span>My Account</span></a>
                <button class="sidebar-close" type="button" data-sidebar-close aria-label="Close navigation">&times;</button>
            </div>
            <nav class="admin-nav">
                <span class="admin-nav-label">Dashboard Panel</span>
                <a class="<?= $activeDashboard ? 'active' : '' ?>" href="<?= site_url('account') ?>"><span class="nav-icon" aria-hidden="true">&#8962;</span><span>Dashboard</span></a>

                <span class="admin-nav-label">Bookings</span>
                <a class="<?= $activeMyBookings ? 'active' : '' ?>" href="<?= site_url('account/bookings') ?>"><span class="nav-icon" aria-hidden="true">&#9635;</span><span>My Bookings</span></a>
                <a class="<?= $activeNewBooking ? 'active' : '' ?>" href="<?= site_url('account/bookings/new') ?>"><span class="nav-icon" aria-hidden="true">&#8853;</span><span>Request a service</span></a>
                <a class="<?= $activePayments ? 'active' : '' ?>" href="<?= site_url('account/payments') ?>"><span class="nav-icon" aria-hidden="true">$</span><span>Online Payments</span></a>

                <span class="admin-nav-label">Manage dates &amp; balance</span>
                <a class="<?= $activePickupDates ? 'active' : '' ?>" href="<?= site_url('account/pickup-dates') ?>"><span class="nav-icon" aria-hidden="true">&#9783;</span><span>Date Leaving Campus</span></a>
                <a class="<?= $activeDropoffDates ? 'active' : '' ?>" href="<?= site_url('account/dropoff-dates') ?>"><span class="nav-icon" aria-hidden="true">&#8596;</span><span>Manage Drop-off Dates</span></a>
                <a class="<?= $activePayHistory ? 'active' : '' ?>" href="<?= site_url('account/payment-history') ?>"><span class="nav-icon" aria-hidden="true">&#8644;</span><span>Payment History Logs</span></a>
                <a class="<?= $activeBalance ? 'active' : '' ?>" href="<?= site_url('account/balance') ?>"><span class="nav-icon" aria-hidden="true">&#9878;</span><span>Balance Management</span></a>

                <span class="admin-nav-label">Account</span>
                <a class="<?= $activeProfile ? 'active' : '' ?>" href="<?= site_url('account/profile') ?>"><span class="nav-icon" aria-hidden="true">&#9678;</span><span>Personal details</span></a>
            </nav>
            <a class="sidebar-site-link" href="<?= site_url('/') ?>" target="_blank" rel="noopener"><span aria-hidden="true">&#8599;</span> Browse services</a>
        </aside>
        <div class="sidebar-scrim" data-sidebar-close></div>

        <div class="admin-content">
            <header class="admin-topbar">
                <button class="sidebar-open" type="button" data-sidebar-open aria-label="Open navigation">&#9776;</button>
                <div class="topbar-title">
                    <span class="topbar-kicker">Epic customer portal</span>
                    <strong><?= esc(trim(explode('|', (string) $title)[0])) ?></strong>
                </div>
                <div class="topbar-actions">
                    <a class="topbar-site" href="<?= site_url('/') ?>" target="_blank" rel="noopener">View website</a>
                    <?php $customerName = trim((string) session()->get('customer_user_name')); ?>
                    <a class="topbar-profile" href="<?= site_url('account/profile') ?>">
                        <span class="admin-avatar"><?= esc(mb_strtoupper(mb_substr($customerName !== '' ? $customerName : 'E', 0, 1))) ?></span>
                        <span><strong><?= esc($customerName !== '' ? $customerName : 'My account') ?></strong><small>Customer</small></span>
                    </a>
                    <form action="<?= site_url('logout') ?>" method="post" data-confirm="Are you sure you want to end your current session?" data-confirm-title="Log out of Epic?" data-confirm-button="Yes, log out" data-loading-title="Signing you out">
                        <?= csrf_field() ?>
                        <button class="topbar-logout" type="submit" aria-label="Log out" title="Log out">&#8618;</button>
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
