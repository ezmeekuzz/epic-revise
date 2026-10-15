<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$paid = array_sum(array_map(static fn (array $transaction): float => $transaction['status'] === 'completed' ? (float) $transaction['amount'] : 0.0, $transactions));
$isStorage = $booking['booking_type'] === 'summer_storage';
$itemTotal = array_sum(array_map(static fn (array $line): float => (float) $line['line_total'], $bookingItems));
$balance = max(0, (float) $booking['total_amount'] - $paid);
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">Booking details &middot; <?= $isStorage ? 'Summer Storage' : 'Summer Advantage' ?></p>
        <h1><?= esc($booking['reference_code']) ?></h1>
        <p>Created <?= esc(date('F j, Y g:i A', strtotime((string) $booking['created_at']))) ?></p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/bookings') ?>">Back</a>
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/bookings/' . $booking['id'] . '/export.pdf') ?>">Download PDF</a>
        <a class="btn btn-primary" href="<?= site_url('admin/bookings/' . $booking['id'] . '/edit') ?>">Edit booking</a>
    </div>
</div>

<div class="detail-grid mb-4">
    <section class="admin-card form-card wide">
        <div class="card-heading">
            <div><h2>Customer and service</h2><p>Booking-owned contact, processing, pickup, and drop-off details.</p></div>
            <span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span>
        </div>
        <dl class="detail-list">
            <div><dt>Customer</dt><dd><?= esc($booking['customer_name']) ?></dd></div>
            <div><dt>Booking type</dt><dd><?= $isStorage ? 'Summer Storage' : 'Summer Advantage' ?></dd></div>
            <div><dt>Email</dt><dd><a href="mailto:<?= esc($booking['customer_email'], 'attr') ?>"><?= esc($booking['customer_email']) ?></a></dd></div>
            <div><dt>Phone / student ID</dt><dd><?= esc($booking['customer_phone'] ?: 'Not provided') ?> &middot; <?= esc($booking['student_id'] ?: 'No student ID') ?></dd></div>
            <div><dt><?= $isStorage ? 'Additional boxes' : 'Boxes' ?></dt><dd><?= number_format((int) $booking['additional_box_quantity']) ?></dd></div>
            <?php if ($isStorage): ?>
                <div><dt>Summer school</dt><dd><?= (int) ($booking['is_summer_school'] ?? 0) === 1 ? 'Yes' : 'No' ?></dd></div>
                <div><dt>Study abroad</dt><dd><?= (int) ($booking['is_study_abroad'] ?? 0) === 1 ? 'Yes - subtotal doubled' : 'No' ?></dd></div>
            <?php endif ?>
            <div><dt>Pickup</dt><dd><?= $booking['pickup_date'] ? esc(date('F j, Y', strtotime((string) $booking['pickup_date']))) : 'Not scheduled' ?><?= $booking['pickup_time'] ? ' at ' . esc(date('g:i A', strtotime((string) $booking['pickup_time']))) : '' ?><small class="d-block text-muted">Status: <?= esc(ucfirst($booking['pickup_status'] ?? 'unscheduled')) ?></small></dd></div>
            <div><dt>Pickup dorm / room</dt><dd><?= esc($dorm['name'] ?? ($booking['pickup_location'] ?: 'Not provided')) ?><?= $booking['room_number'] ? ' - Room ' . esc($booking['room_number']) : '' ?></dd></div>
            <div><dt>Drop-off</dt><dd><?= $booking['dropoff_date'] ? esc(date('F j, Y', strtotime((string) $booking['dropoff_date']))) : 'Not scheduled' ?><?= $booking['dropoff_time'] ? ' at ' . esc(date('g:i A', strtotime((string) $booking['dropoff_time']))) : '' ?><small class="d-block text-muted">Status: <?= esc(ucfirst($booking['dropoff_status'] ?? 'unscheduled')) ?></small></dd></div>
            <div><dt>Drop-off location / room</dt><dd><?= esc($booking['dropoff_location'] ?: 'Not provided') ?><?= $booking['dropoff_room_number'] ? ' - Room ' . esc($booking['dropoff_room_number']) : '' ?></dd></div>
            <div><dt>Warehouse row</dt><dd>
                <form class="d-flex gap-2 align-items-center" action="<?= site_url('admin/bookings/' . $booking['id'] . '/warehouse') ?>" method="post" data-loading-title="Updating warehouse row" novalidate>
                    <?= csrf_field() ?>
                    <input class="form-control form-control-sm" style="max-width:120px" name="row_in_warehouse" type="number" min="1" step="1" placeholder="Row #" value="<?= esc($booking['row_in_warehouse'] ?? '', 'attr') ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                </form>
            </dd></div>
        </dl>
        <?php if ($booking['admin_notes']): ?><div class="notice-panel mt-3"><strong>Internal notes:</strong><br><?= nl2br(esc($booking['admin_notes'])) ?></div><?php endif ?>
    </section>

    <aside class="admin-card form-card">
        <div class="card-heading"><div><h2>Financial summary</h2><p>Snapshotted booking prices and ledger totals.</p></div></div>
        <?php if ($isStorage): ?>
            <div class="summary-row"><span>Base price</span><strong>$<?= number_format((float) $booking['base_price'], 2) ?></strong></div>
            <div class="summary-row"><span>Selected items</span><strong>$<?= number_format($itemTotal, 2) ?></strong></div>
        <?php endif ?>
        <div class="summary-row"><span><?= $isStorage ? 'Additional boxes' : 'Boxes' ?></span><strong>$<?= number_format((float) $booking['addtl_box_total_amount'], 2) ?></strong></div>
        <?php if ($isStorage && (int) ($booking['is_summer_school'] ?? 0) === 1): ?>
            <div class="summary-row"><span>Summer school</span><strong>$<?= number_format((float) $booking['summer_school_fee'], 2) ?></strong></div>
        <?php endif ?>
        <?php if ($isStorage && (int) ($booking['is_study_abroad'] ?? 0) === 1): ?>
            <div class="summary-row"><span>Study abroad adjustment</span><strong>$<?= number_format((float) $booking['study_abroad_additional_storage_price'], 2) ?></strong></div>
        <?php endif ?>
        <div class="summary-row"><span>Subtotal</span><strong>$<?= number_format((float) $booking['subtotal'], 2) ?></strong></div>
        <div class="summary-row"><span>Discount</span><strong>-$<?= number_format((float) $booking['discount_amount'], 2) ?></strong></div>
        <div class="summary-row total"><span>Total</span><strong>$<?= number_format((float) $booking['total_amount'], 2) ?></strong></div>
        <div class="summary-row"><span>Net paid</span><strong>$<?= number_format($paid, 2) ?></strong></div>
        <div class="summary-row"><span>Balance</span><strong>$<?= number_format($balance, 2) ?></strong></div>
        <div class="mt-3"><span class="status-pill status-<?= esc($booking['payment_status'], 'attr') ?>"><?= esc(ucfirst($booking['payment_status'])) ?></span></div>
        <a class="btn btn-outline-primary w-100 mt-4" href="<?= site_url('admin/payments/new?booking=' . $booking['id']) ?>">Record payment</a>
    </aside>
</div>

<section class="admin-card mb-4">
    <div class="card-heading"><div><h2>Customer notifications</h2><p>Send transactional email to <?= esc($booking['customer_email']) ?> for this booking.</p></div></div>
    <div class="card-body notification-actions">
        <div class="notification-action">
            <form action="<?= site_url('admin/bookings/' . $booking['id'] . '/notify/confirmation') ?>" method="post" data-loading-title="Sending booking confirmation" novalidate><?= csrf_field() ?><button class="btn btn-outline-primary" type="submit">Send booking confirmation</button></form>
        </div>
        <div class="notification-action">
            <form action="<?= site_url('admin/bookings/' . $booking['id'] . '/notify/balance-due') ?>" method="post" data-loading-title="Sending balance due notice" novalidate><?= csrf_field() ?><button class="btn btn-outline-primary" type="submit"<?= $balance < 0.01 ? ' disabled' : '' ?>>Send balance due reminder</button></form>
            <?php if ($balance < 0.01): ?><p class="field-hint">No outstanding balance.</p><?php endif ?>
        </div>
        <div class="notification-action">
            <form action="<?= site_url('admin/bookings/' . $booking['id'] . '/notify/pickup-reminder') ?>" method="post" data-loading-title="Sending pickup reminder" novalidate><?= csrf_field() ?><button class="btn btn-outline-primary" type="submit"<?= $booking['pickup_date'] ? '' : ' disabled' ?>>Send pickup reminder</button></form>
            <?php if (! $booking['pickup_date']): ?><p class="field-hint">No pickup date scheduled.</p><?php endif ?>
        </div>
        <div class="notification-action">
            <form action="<?= site_url('admin/bookings/' . $booking['id'] . '/notify/dropoff-reminder') ?>" method="post" data-loading-title="Sending drop-off reminder" novalidate><?= csrf_field() ?><button class="btn btn-outline-primary" type="submit"<?= $booking['dropoff_date'] ? '' : ' disabled' ?>>Send drop-off reminder</button></form>
            <?php if (! $booking['dropoff_date']): ?><p class="field-hint">No drop-off date scheduled.</p><?php endif ?>
        </div>
    </div>
</section>

<section class="admin-card mb-4">
    <div class="card-heading"><div><h2>Booking items and sizes</h2><p><?= $isStorage ? 'Prices are snapshotted so historical bookings remain accurate.' : 'Summer Advantage is priced only by box quantity.' ?></p></div></div>
    <?php if ($isStorage): ?>
        <form class="filter-bar" action="<?= site_url('admin/bookings/' . $booking['id'] . '/items') ?>" method="post" data-loading-title="Adding booking item" novalidate>
            <?= csrf_field() ?>
            <div><label class="visually-hidden" for="item_size_id">Storage item and size</label><select class="form-select" id="item_size_id" name="item_size_id" required><option value="">Select an active item size</option><?php foreach ($availableItems as $item): ?><option value="<?= (int) $item['id'] ?>"><?= esc($item['item_name'] . ' - ' . $item['name']) ?> - $<?= number_format((float) $item['unit_price'], 2) ?></option><?php endforeach ?></select></div>
            <div><label class="visually-hidden" for="quantity">Quantity</label><input class="form-control" id="quantity" name="quantity" type="number" min="1" max="100" value="1" required></div>
            <button class="btn btn-primary" type="submit"<?= $availableItems === [] ? ' disabled' : '' ?>>Add item</button>
            <?php if ($availableItems === []): ?><a class="btn btn-link" href="<?= site_url('admin/items') ?>">Create an item size first</a><?php endif ?>
        </form>
    <?php endif ?>
    <?php if ($bookingItems === []): ?>
        <div class="empty-state"><strong><?= $isStorage ? 'No additional items' : 'Box-only booking' ?></strong><p><?= $isStorage ? 'No additional size-specific items are attached to this booking.' : 'Items and item-size charges do not apply to Summer Advantage.' ?></p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table js-datatable admin-table align-middle mb-0"><thead><tr><th>Item</th><th>Size</th><th>Unit price</th><th>Quantity</th><th>Line total</th><th></th></tr></thead><tbody>
        <?php foreach ($bookingItems as $line): ?><tr><td><strong><?= esc($line['item_name']) ?></strong></td><td><?= esc($line['size_name'] ?: 'Standard') ?></td><td>$<?= number_format((float) $line['unit_price'], 2) ?></td><td><?= number_format((int) $line['quantity']) ?></td><td><strong>$<?= number_format((float) $line['line_total'], 2) ?></strong></td><td class="text-end"><form action="<?= site_url('admin/bookings/' . $booking['id'] . '/items/' . $line['id'] . '/delete') ?>" method="post" data-confirm="Remove this item from the booking?" data-confirm-title="Remove booking item?" data-confirm-button="Yes, remove" data-confirm-danger="true" data-loading-title="Removing item"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Remove</button></form></td></tr><?php endforeach ?>
        </tbody></table></div>
    <?php endif ?>
</section>

<div class="row g-4">
    <div class="col-xl-6"><section class="admin-card h-100"><div class="card-heading"><div><h2>Payments</h2><p>Administrative payment records for this booking.</p></div><a href="<?= site_url('admin/payments/new?booking=' . $booking['id']) ?>">Record payment</a></div><?php if ($payments === []): ?><div class="empty-state"><strong>No payments</strong><p>No payment attempts have been recorded.</p></div><?php else: ?><div class="table-responsive"><table class="table js-datatable admin-table mb-0"><thead><tr><th>Reference</th><th>Amount</th><th>Provider</th><th>Status</th></tr></thead><tbody><?php foreach ($payments as $payment): ?><tr><td><strong><?= esc($payment['reference']) ?></strong><small><?= esc($payment['external_reference'] ?: date('M j, Y', strtotime((string) $payment['created_at']))) ?></small></td><td><?= esc($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td><td><?= esc(ucfirst($payment['provider'])) ?></td><td><span class="status-pill status-<?= esc($payment['status'], 'attr') ?>"><?= esc(ucfirst($payment['status'])) ?></span></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></section></div>
    <div class="col-xl-6"><section class="admin-card h-100"><div class="card-heading"><div><h2>Transaction activity</h2><p>Immutable financial events for audit and reconciliation.</p></div><a href="<?= site_url('admin/transactions') ?>">Full ledger</a></div><?php if ($transactions === []): ?><div class="empty-state"><strong>No transactions</strong><p>Completed payments and refunds appear here.</p></div><?php else: ?><div class="table-responsive"><table class="table js-datatable admin-table mb-0"><thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Processed</th></tr></thead><tbody><?php foreach ($transactions as $transaction): ?><tr><td><strong><?= esc($transaction['reference']) ?></strong></td><td><?= esc(ucfirst($transaction['type'])) ?></td><td class="<?= (float) $transaction['amount'] < 0 ? 'amount-negative' : 'amount-positive' ?>"><?= (float) $transaction['amount'] < 0 ? '-' : '' ?><?= esc($transaction['currency']) ?> <?= number_format(abs((float) $transaction['amount']), 2) ?></td><td><?= esc(date('M j, Y g:i a', strtotime((string) $transaction['processed_at']))) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></section></div>
</div>
<?php if ($customerProfile !== null): ?><section class="admin-card"><h2>Student, parent, and shipping details</h2><?= view('partials/customer_summary', ['user' => $customerProfile]) ?></section><?php endif ?>
<?php if (! empty($booking['terms_snapshot'])): ?>
    <details class="portal-card mt-4"><summary>Terms accepted for this order</summary><p class="mt-3">Accepted <?= esc($booking['terms_accepted_at'] ?? '') ?></p><pre class="order-terms"><?= esc($booking['terms_snapshot']) ?></pre></details>
<?php endif ?>
<?= $this->endSection() ?>
