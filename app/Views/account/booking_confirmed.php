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
                <div class="text-start mt-4">
                    <p>Thank you for your order! You'll receive an email from our office with your invoice and instructions for setting up your account. If you don't see it in your inbox, please check your spam or junk folder.</p>
                    <p>In the bottom of the email, click <strong>Create Account</strong> to complete your account setup. Once your account is complete, you can view your order, pay any outstanding balance, and add items to your order directly on our website.</p>
                    <p class="mb-0">If you have any questions, please send an email to our office <a href="mailto:support@epicstoragehpu.com">support@epicstoragehpu.com</a> or call our office at <a href="tel:3365495216">336 549 5216</a>.</p>
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
