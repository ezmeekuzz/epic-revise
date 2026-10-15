<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<?php $payable = array_values(array_filter($bookings, static fn (array $booking): bool => $booking['balance'] >= 0.01 && $booking['status'] !== 'cancelled')); ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span>
                <h1 class="h2 mt-2 mb-2">Online payments</h1>
                <p class="mb-0 text-muted-brand">Pay any booking balance securely through Square. Card details are never stored by EPIC.</p>
            </div>

            <div class="portal-card">
                <?php if ($bookings === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">No bookings yet</strong><p class="text-muted-brand mt-2 mb-0">Once you request a service, you can pay for it here.</p></div>
                <?php elseif ($payable === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">You're all paid up</strong><p class="text-muted-brand mt-2 mb-0">There is no outstanding balance on any of your bookings.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table js-datatable align-middle mb-0">
                            <thead><tr><th>Booking</th><th>Service</th><th>Total</th><th>Paid</th><th>Balance due</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($payable as $booking): ?>
                                <tr>
                                    <td><strong><?= esc($booking['reference_code']) ?></strong></td>
                                    <td><?= esc($booking['service_name']) ?></td>
                                    <td>$<?= number_format((float) $booking['total_amount'], 2) ?></td>
                                    <td>$<?= number_format((float) $booking['paid'], 2) ?></td>
                                    <td><strong>$<?= number_format((float) $booking['balance'], 2) ?></strong></td>
                                    <td><a class="btn btn-sm btn-brand-primary" href="<?= site_url('account/bookings/' . $booking['id'] . '/payment') ?>">Pay now</a></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
