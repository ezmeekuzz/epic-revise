<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<?php
$netPaid = array_sum(array_map(static fn (array $transaction): float => (float) $transaction['amount'], $transactions));
$isStorage = $booking['booking_type'] === 'summer_storage';
$itemTotal = array_sum(array_map(static fn (array $item): float => (float) $item['line_total'], $items));
$boxTotal = (float) ($booking['addtl_box_total_amount'] ?? 0);
?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div>
                    <span class="eyebrow">Booking details</span>
                    <h1 class="h2 mt-2 mb-2"><?= esc($booking['reference_code']) ?></h1>
                    <p class="mb-0 text-muted-brand">Submitted <?= esc(date('F j, Y', strtotime((string) $booking['created_at']))) ?></p>
                </div>
                <a class="btn btn-brand-outline" href="<?= site_url('account') ?>">Back to account</a>
            </div>

            <?php if (! in_array($booking['status'], ['completed', 'cancelled'], true)): ?>
                <p><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/' . $booking['id'] . '/items') ?>">Add additional items</a></p>
            <?php endif ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="portal-card h-100">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <h2 class="h4">Pickup, drop-off &amp; service</h2>
                            <div class="d-flex flex-column align-items-end gap-2"><span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span><a class="btn btn-sm btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id'] . '/pickup') ?>">Manage pickup &amp; drop-off</a></div>
                        </div>
                        <dl class="account-details mb-0">
                            <div><dt>Service</dt><dd><?= esc($booking['service_name']) ?></dd></div>
                            <div><dt><?= $isStorage ? 'Additional boxes' : 'Boxes' ?></dt><dd><?= number_format((int) $booking['additional_box_quantity']) ?></dd></div>
                            <?php if ($isStorage): ?>
                                <div><dt>Summer school</dt><dd><?= (int) ($booking['is_summer_school'] ?? 0) === 1 ? 'Yes' : 'No' ?></dd></div>
                                <div><dt>Study abroad</dt><dd><?= (int) ($booking['is_study_abroad'] ?? 0) === 1 ? 'Yes - service subtotal doubled' : 'No' ?></dd></div>
                            <?php endif ?>
                            <div><dt>Phone</dt><dd><?= esc($booking['customer_phone'] ?: 'Not provided') ?></dd></div>
                        </dl>

                        <p class="account-details-heading">Pickup <span class="status-pill status-<?= esc($booking['pickup_status'] ?? 'unscheduled', 'attr') ?>"><?= esc(ucfirst($booking['pickup_status'] ?? 'unscheduled')) ?></span></p>
                        <dl class="account-details mb-0">
                            <div><dt>Date</dt><dd><?= $booking['pickup_date'] ? esc(date('F j, Y', strtotime((string) $booking['pickup_date']))) : 'Awaiting schedule' ?></dd></div>
                            <div><dt>Time</dt><dd><?= $booking['pickup_time'] ? esc(date('g:i A', strtotime((string) $booking['pickup_time']))) : 'Awaiting schedule' ?></dd></div>
                            <div><dt>Dorm / location</dt><dd><?= esc($dorm['name'] ?? ($booking['pickup_location'] ?: 'Not provided')) ?></dd></div>
                            <div><dt>Room</dt><dd><?= esc($booking['room_number'] ?: 'Not provided') ?></dd></div>
                        </dl>

                        <p class="account-details-heading">Drop-off / redelivery <span class="status-pill status-<?= esc($booking['dropoff_status'] ?? 'unscheduled', 'attr') ?>"><?= esc(ucfirst($booking['dropoff_status'] ?? 'unscheduled')) ?></span></p>
                        <dl class="account-details mb-0">
                            <div><dt>Date</dt><dd><?= $booking['dropoff_date'] ? esc(date('F j, Y', strtotime((string) $booking['dropoff_date']))) : 'Awaiting schedule' ?></dd></div>
                            <div><dt>Time</dt><dd><?= $booking['dropoff_time'] ? esc(date('g:i A', strtotime((string) $booking['dropoff_time']))) : 'Awaiting schedule' ?></dd></div>
                            <div><dt>Dorm / location</dt><dd><?= esc($booking['dropoff_location'] ?: 'Not provided') ?></dd></div>
                            <div><dt>Room</dt><dd><?= esc($booking['dropoff_room_number'] ?: 'Not provided') ?></dd></div>
                        </dl>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="portal-card h-100">
                        <h2 class="h4">Price summary</h2>
                        <dl class="account-details mb-0">
                            <?php if ($isStorage): ?>
                                <div><dt>Storage base</dt><dd>$<?= number_format((float) $booking['base_price'], 2) ?></dd></div>
                                <div><dt>Selected items</dt><dd>$<?= number_format($itemTotal, 2) ?></dd></div>
                            <?php endif ?>
                            <div><dt><?= $isStorage ? 'Additional boxes' : 'Boxes' ?></dt><dd>$<?= number_format($boxTotal, 2) ?></dd></div>
                            <?php if ($isStorage && (int) ($booking['is_summer_school'] ?? 0) === 1): ?>
                                <div><dt>Summer school</dt><dd>$<?= number_format((float) $booking['summer_school_fee'], 2) ?></dd></div>
                            <?php endif ?>
                            <?php if ($isStorage && (int) ($booking['is_study_abroad'] ?? 0) === 1): ?>
                                <div><dt>Study abroad adjustment</dt><dd>$<?= number_format((float) $booking['study_abroad_additional_storage_price'], 2) ?></dd></div>
                            <?php endif ?>
                            <div><dt>Total</dt><dd><strong>$<?= number_format((float) $booking['total_amount'], 2) ?></strong></dd></div>
                            <div><dt>Net paid</dt><dd>$<?= number_format($netPaid, 2) ?></dd></div>
                            <div><dt>Balance</dt><dd>$<?= number_format(max(0, (float) $booking['total_amount'] - $netPaid), 2) ?></dd></div>
                            <div><dt>Status</dt><dd><span class="status-pill status-<?= esc($booking['payment_status'], 'attr') ?>"><?= esc(ucfirst($booking['payment_status'])) ?></span></dd></div>
                        </dl>
                        <?php if ($booking['payment_status'] !== 'paid'): ?><a class="btn btn-brand-primary w-100 mt-4" href="<?= site_url('account/bookings/' . $booking['id'] . '/payment') ?>">Continue to payment</a><?php endif ?>
                    </div>
                </div>
            </div>

            <?php if ($isStorage): ?>
                <div class="portal-card mt-4">
                    <h2 class="h4">Stored items</h2>
                    <?php if ($items === []): ?>
                        <p class="text-muted-brand mb-0">No additional items were selected for this booking.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table admin-table align-middle mb-0">
                                <thead><tr><th>Item</th><th>Size</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead>
                                <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= esc($item['item_name']) ?></td>
                                        <td><?= esc($item['size_name'] ?: 'Standard') ?></td>
                                        <td><?= (int) $item['quantity'] ?></td>
                                        <td>$<?= number_format((float) $item['unit_price'], 2) ?></td>
                                        <td>$<?= number_format((float) $item['line_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            <?php else: ?>
                <div class="portal-card mt-4">
                    <h2 class="h4">Summer Advantage</h2>
                    <p class="text-muted-brand mb-0">This service is priced only by the number of boxes selected.</p>
                </div>
            <?php endif ?>

            <?php if ($payments !== []): ?>
                <div class="portal-card mt-4">
                    <h2 class="h4">Payment activity</h2>
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= esc(date('M j, Y', strtotime((string) $payment['created_at']))) ?></td>
                                    <td><?= esc(ucfirst($payment['method'])) ?></td>
                                    <td><?= esc($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                                    <td><span class="status-pill status-<?= esc($payment['status'], 'attr') ?>"><?= esc(ucfirst($payment['status'])) ?></span></td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </section>
</main>
<?php if (! empty($booking['terms_snapshot'])): ?>
    <details class="portal-card mt-4"><summary>Terms accepted for this order</summary><p class="mt-3">Accepted <?= esc($booking['terms_accepted_at'] ?? '') ?></p><pre class="order-terms"><?= esc($booking['terms_snapshot']) ?></pre></details>
<?php endif ?>
<?= $this->endSection() ?>
