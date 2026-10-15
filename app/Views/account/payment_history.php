<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span>
                <h1 class="h2 mt-2 mb-2">Payment history logs</h1>
                <p class="mb-0 text-muted-brand">Every payment attempt recorded against your Epic Storage bookings.</p>
            </div>

            <div class="portal-card">
                <?php if ($payments === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">No payments yet</strong><p class="text-muted-brand mt-2 mb-0">Payments you make toward a booking will be logged here.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table js-datatable align-middle mb-0">
                            <thead><tr><th>Date</th><th>Booking</th><th>Method</th><th>Amount</th><th>Status</th><th>Receipt</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <?php
                                $receiptUrl = null;
                                if (preg_match('#Square receipt:\s*(\S+)#', (string) ($payment['notes'] ?? ''), $matches) === 1) {
                                    $receiptUrl = $matches[1];
                                }
                                ?>
                                <tr>
                                    <td><?= esc(date('M j, Y g:i A', strtotime((string) $payment['created_at']))) ?></td>
                                    <td><strong><?= esc($payment['booking_reference']) ?></strong><small class="d-block text-muted-brand"><?= esc($payment['service_name']) ?></small></td>
                                    <td><?= esc(ucfirst($payment['method'])) ?> <small class="d-block text-muted-brand"><?= esc(ucfirst($payment['provider'])) ?></small></td>
                                    <td><?= esc($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                                    <td><span class="status-pill status-<?= esc($payment['status'], 'attr') ?>"><?= esc(ucfirst($payment['status'])) ?></span></td>
                                    <td><?php if ($receiptUrl !== null): ?><a href="<?= esc($receiptUrl, 'attr') ?>" target="_blank" rel="noopener">View</a><?php else: ?>&mdash;<?php endif ?></td>
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
