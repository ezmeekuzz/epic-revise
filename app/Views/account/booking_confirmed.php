<?php
/*
 * Shown after a booking made without an account. It must read the same whether or not the
 * email already has an account: only the email (which only the address owner receives) says so.
 */
?>
<?= $this->extend('layouts/site') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/booking-flow.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/booking-flow.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="portal-card confirmation-card">
                <div class="confirmation-icon mb-3" aria-hidden="true">&#10003;</div>
                <?php if ($balance < 0.01): ?>
                    <h1 class="h3 mb-2">Payment received</h1>
                    <p class="mb-1">Booking <strong><?= esc($booking['reference_code']) ?></strong> is confirmed.</p>
                <?php else: ?>
                    <h1 class="h3 mb-2">Booking saved</h1>
                    <p class="mb-1">Booking <strong><?= esc($booking['reference_code']) ?></strong> is saved. Amount due: <strong>$<?= number_format($balance, 2) ?></strong>.</p>
                <?php endif ?>
                <p class="text-muted-brand">We emailed your booking details to <strong><?= esc($maskedEmail) ?></strong>.</p>

                <div class="notice-panel text-start mt-4">
                    <strong class="d-block mb-1">Already have an EPIC account?</strong>
                    <span>The email has a button to add this booking to it in one click. New to EPIC? The email also lets you set up an account to track this booking, pay balances and schedule leaving campus.</span>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 mt-4">
                    <?php if ($balance >= 0.01): ?><a class="btn btn-brand-primary" href="<?= site_url('book/' . $booking['id'] . '/pay') ?>">Pay now</a><?php endif ?>
                    <a class="btn btn-brand-outline" href="<?= site_url('/') ?>">Back to home</a>
                </div>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
