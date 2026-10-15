<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc($description, 'attr') ?>">
    <meta name="robots" content="<?= esc($robots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1', 'attr') ?>">
    <meta name="theme-color" content="#083150">
    <title><?= esc($title) ?></title>
    <link rel="canonical" href="<?= esc($canonicalUrl, 'attr') ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="EPIC Storage Solutions">
    <meta property="og:title" content="<?= esc($title, 'attr') ?>">
    <meta property="og:description" content="<?= esc($description, 'attr') ?>">
    <meta property="og:url" content="<?= esc($canonicalUrl, 'attr') ?>">
    <?php if ($socialImage !== null): ?>
        <meta property="og:image" content="<?= esc($socialImage, 'attr') ?>">
    <?php endif ?>
    <meta name="twitter:card" content="<?= $socialImage !== null ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= esc($title, 'attr') ?>">
    <meta name="twitter:description" content="<?= esc($description, 'attr') ?>">
    <?php if ($socialImage !== null): ?>
        <meta name="twitter:image" content="<?= esc($socialImage, 'attr') ?>">
    <?php endif ?>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jura:wght@400;500;600;700;800&amp;family=Poppins:wght@700;800;900&amp;family=Montserrat:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/home.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/home.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css') ?>">
    <?= $this->renderSection('head') ?>
</head>
<body<?= $activePage === 'home' ? ' class="page-home"' : '' ?>>
<?php $this->section('header') ?>
<header class="nav2<?= $activePage === 'home' ? '' : ' sticky-top' ?>">
    <nav class="navbar navbar-expand-lg py-3" aria-label="Primary navigation">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('/') ?>">
                <img src="<?= base_url('assets/images/epic-logo-white.png') ?>" alt="EPIC Storage Solutions">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link<?= $activePage === 'about' ? ' active' : '' ?>" href="<?= site_url('about') ?>"<?= $activePage === 'about' ? ' aria-current="page"' : '' ?>>About us</a></li>
                    <li class="nav-item"><a class="nav-link<?= $activePage === 'services' ? ' active' : '' ?>" href="<?= site_url('services') ?>"<?= $activePage === 'services' ? ' aria-current="page"' : '' ?>>Services</a></li>
                    <li class="nav-item"><a class="nav-link<?= $activePage === 'faqs' ? ' active' : '' ?>" href="<?= site_url('faqs') ?>"<?= $activePage === 'faqs' ? ' aria-current="page"' : '' ?>>FAQs</a></li>
                    <li class="nav-item"><a class="nav-link<?= $activePage === 'testimonials' ? ' active' : '' ?>" href="<?= site_url('testimonials') ?>"<?= $activePage === 'testimonials' ? ' aria-current="page"' : '' ?>>Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link<?= $activePage === 'contact' ? ' active' : '' ?>" href="<?= site_url('contact') ?>"<?= $activePage === 'contact' ? ' aria-current="page"' : '' ?>>Contact us</a></li>
                </ul>
                <div class="nav-actions d-flex align-items-center gap-3 ms-lg-3">
                    <?php $isCustomerSession = session()->get('customer_is_logged_in') && (int) session()->get('customer_user_id') > 0; ?>
                    <?php if ($isCustomerSession): ?>
                        <?php $headerUserName = trim((string) session()->get('customer_user_name')); $headerInitial = mb_strtoupper(mb_substr($headerUserName !== '' ? $headerUserName : 'E', 0, 1)); ?>
                        <div class="nav-account">
                            <a class="nav-user<?= $activePage === 'account' ? ' active' : '' ?>" href="<?= site_url('account') ?>"<?= $activePage === 'account' ? ' aria-current="page"' : '' ?>>
                                <span class="nav-user-avatar" aria-hidden="true"><?= esc($headerInitial) ?></span>
                                <span class="nav-user-meta"><small>Signed in as</small><strong><?= esc($headerUserName !== '' ? $headerUserName : 'EPIC customer') ?></strong></span>
                            </a>
                            <form action="<?= site_url('logout') ?>" method="post" class="nav-logout-form" data-confirm="Are you sure you want to end your current session?" data-confirm-title="Log out of EPIC?" data-confirm-button="Yes, log out" data-loading-title="Signing you out">
                                <?= csrf_field() ?>
                                <button class="nav-logout" type="submit" aria-label="Log out of your EPIC account">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 4H6.5A1.5 1.5 0 0 0 5 5.5v13A1.5 1.5 0 0 0 6.5 20H10M14 16l4-4-4-4M18 12H9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span>Log out</span>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <a class="nav-login nav-login-guest<?= $activePage === 'login' ? ' active' : '' ?>" href="<?= site_url('login') ?>"<?= $activePage === 'login' ? ' aria-current="page"' : '' ?>><span>Log in</span><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M13 5l7 7-7 7M20 12H4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                    <?php endif ?>
                    <a class="btn btn-brand-primary" href="<?= site_url('services') ?>">Order Now</a>
                </div>
            </div>
        </div>
    </nav>
</header>
<?php $this->endSection() ?>

<?php $this->section('footer') ?>
<footer class="footer2">
    <div class="container">
        <div class="footer2-main">
            <div class="footer2-intro">
                <img class="footer2-logo" src="<?= base_url('assets/images/epic-logo-white.png') ?>" alt="EPIC Storage Solutions">
                <p>Secure, simple summer storage for High Point University students. We collect, store, and return your belongings when you need them.</p>
                <a class="footer2-cta" href="<?= site_url('services') ?>">Start your order <span aria-hidden="true">&rarr;</span></a>
            </div>
            <nav class="footer2-links" aria-label="Footer navigation">
                <h4>Explore</h4>
                <a href="<?= site_url('about') ?>">About us</a>
                <a href="<?= site_url('services') ?>">Services</a>
                                <a href="<?= site_url('faqs') ?>">FAQs</a>
            </nav>
            <div class="footer2-contact">
                <h4>Get in touch</h4>
                <a class="footer2-contact-item" href="tel:3365495216"><span class="footer2-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.4c.9.4 1.9.6 2.9.7a2 2 0 0 1 1.7 2.1z"/></svg></span><span><small>Call us</small><strong>336.549.5216</strong></span></a>
                <a class="footer2-contact-item" href="mailto:support@epicstoragehpu.com"><span class="footer2-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg></span><span><small>Email us</small><strong>Sales Support</strong></span></a>
                <div class="footer2-socials" aria-label="Social media links">
                    <a href="#linkedin-pending" aria-label="LinkedIn (link pending)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7 10v7M7 7v.01M11 17v-4.5a2 2 0 0 1 4 0V17"/></svg></a>
                    <a href="https://www.facebook.com/epicstoragesolutions/" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M15 8h2V5h-2a4 4 0 0 0-4 4v2H9v3h2v6h3v-6h2.2l.8-3H14V9c0-.6.4-1 1-1z"/></svg></a>
                </div>
            </div>
        </div>
        <div class="footer2-bottom">
            <span>&copy; <?= date('Y') ?> EPIC Storage Solutions. All rights reserved.</span>
            <span>Your Privacy Is Our Priority. <a href="<?= site_url('terms') ?>">View our terms and privacy choice</a></span>
        </div>
    </div>
</footer>
<?php $this->endSection() ?>

<?= $this->renderSection('header') ?>
<?= view('components/notification') ?>
<?= $this->renderSection('content') ?>
<?= $this->renderSection('footer') ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('assets/js/notifications.js') ?>"></script>
<script src="<?= base_url('assets/js/main.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/main.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
