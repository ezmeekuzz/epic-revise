<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span>
                <h1 class="h2 mt-2 mb-2">Balance management</h1>
                <p class="mb-0 text-muted-brand">Track and pay the outstanding balance across every EPIC Storage booking.</p>
            </div>

            <div class="balance-banner mb-4">
                <div><h2 class="h4">Total outstanding balance</h2><p>Across all of your EPIC Storage bookings.</p></div>
                <strong class="balance-banner-amount">$<?= number_format((float) $totalBalance, 2) ?></strong>
            </div>

            <div class="portal-card">
                <?php if ($bookings === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">No bookings yet</strong><p class="text-muted-brand mt-2 mb-0">Balances for your bookings will appear here.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table js-datatable align-middle mb-0">
                            <thead><tr><th>Booking</th><th>Service</th><th>Total</th><th>Paid</th><th>Balance</th><th>Progress</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <?php $progress = (float) $booking['total_amount'] > 0 ? min(100, (int) round(((float) $booking['paid'] / (float) $booking['total_amount']) * 100)) : 100; ?>
                                <tr>
                                    <td><strong><?= esc($booking['reference_code']) ?></strong></td>
                                    <td><?= esc($booking['service_name']) ?></td>
                                    <td>$<?= number_format((float) $booking['total_amount'], 2) ?></td>
                                    <td>$<?= number_format((float) $booking['paid'], 2) ?></td>
                                    <td><strong>$<?= number_format((float) $booking['balance'], 2) ?></strong></td>
                                    <td><div class="progress balance-progress" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:<?= $progress ?>%"><?= $progress ?>%</div></div></td>
                                    <td><?php if ($booking['balance'] >= 0.01 && $booking['status'] !== 'cancelled'): ?><a class="btn btn-sm btn-brand-primary" href="<?= site_url('account/bookings/' . $booking['id'] . '/payment') ?>">Pay now</a><?php else: ?><span class="status-pill status-active">Paid</span><?php endif ?></td>
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
