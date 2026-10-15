<?= $this->extend('layouts/account') ?>

<?php if ($square['ready']): ?>
<?= $this->section('head') ?>
<script src="<?= esc($square['sdkUrl'], 'attr') ?>"></script>
<?= $this->endSection() ?>
<?php endif ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div><span class="eyebrow">Secure checkout</span><h1 class="h2 mt-2 mb-2">Complete your payment</h1><p class="mb-0 text-muted-brand">Your service request is saved. Pay the booking balance securely through Square.</p></div>
                <a class="btn btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id']) ?>">View booking</a>
            </div>

            <div class="payment-checkout-grid">
                <section class="portal-card payment-card" aria-labelledby="paymentHeading">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4"><div><p class="eyebrow mb-1">Square payment</p><h2 class="h4 mb-1" id="paymentHeading">Card details</h2><p class="text-muted-brand small mb-0">Card information is securely collected by Square and is not stored by Epic.</p></div><span class="payment-environment"><?= esc($square['mode'] === 'live' ? 'Production' : 'Sandbox') ?></span></div>

                    <?php if (! $square['ready']): ?>
                        <div class="booking-empty-state text-start"><strong class="d-block mb-2">Online payment is not available yet</strong><span>Your booking remains safely saved. An administrator must enable Square and configure its Application ID, Access Token, and Location ID before card payment can continue.</span></div>
                        <div class="d-flex flex-column flex-sm-row gap-2 mt-4"><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/' . $booking['id']) ?>">Return to booking</a><a class="btn btn-brand-outline" href="<?= site_url('contact') ?>">Contact support</a></div>
                    <?php else: ?>
                        <form data-square-payment-form novalidate>
                            <div id="square-card-container" class="square-card-container" aria-label="Secure Square card fields"></div>
                            <div class="form-check mt-3"><input class="form-check-input" id="payment_terms" type="checkbox" required><label class="form-check-label" for="payment_terms">I authorize Epic Storage Solutions to charge <strong><?= esc($square['currency']) ?> $<?= number_format($balance, 2) ?></strong> for this booking and agree to the <a href="<?= site_url('terms') ?>" target="_blank" rel="noopener">terms</a>.</label></div>
                            <div class="payment-inline-status mt-3" data-payment-status role="status" aria-live="polite"></div>
                            <button class="btn btn-brand-primary w-100 mt-4" type="submit" data-payment-submit disabled>Pay <?= esc($square['currency']) ?> $<?= number_format($balance, 2) ?></button>
                            <p class="field-hint text-center mt-3 mb-0">Do not close or refresh this page while Square is processing your payment.</p>
                        </form>
                    <?php endif ?>
                </section>

                <aside class="portal-card payment-order-card" aria-labelledby="orderSummaryHeading">
                    <p class="eyebrow">Order summary</p><h2 class="h4" id="orderSummaryHeading"><?= esc($booking['reference_code']) ?></h2>
                    <dl class="account-details mb-0">
                        <div><dt>Service</dt><dd><?= esc($booking['service_name']) ?></dd></div>
                        <div><dt>Customer</dt><dd><?= esc($booking['customer_name']) ?></dd></div>
                        <div><dt>Subtotal</dt><dd>$<?= number_format((float) $booking['subtotal'], 2) ?></dd></div>
                        <?php if ((float) $booking['discount_amount'] > 0): ?><div><dt>Discount</dt><dd>−$<?= number_format((float) $booking['discount_amount'], 2) ?></dd></div><?php endif ?>
                        <div><dt>Booking total</dt><dd>$<?= number_format((float) $booking['total_amount'], 2) ?></dd></div>
                    </dl>
                    <div class="payment-balance"><span>Amount due</span><strong><?= esc($square['currency']) ?> $<?= number_format($balance, 2) ?></strong></div>
                    <p class="field-hint mb-0">Pickup details remain optional and can be completed from your booking after payment.</p>
                </aside>
            </div>
        </div>
    </section>
</main>
<?php if ($square['ready']): ?><script id="squarePaymentConfig" type="application/json"><?= json_encode($clientConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script><?php endif ?>
<?= $this->endSection() ?>

<?php if ($square['ready']): ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/square-payment.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/square-payment.js') ?>"></script>
<?= $this->endSection() ?>
<?php endif ?>
