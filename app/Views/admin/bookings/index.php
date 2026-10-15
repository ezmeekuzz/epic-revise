<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $exportQuery = http_build_query(array_filter(['type' => $bookingType, 'q' => $filters['q'], 'status' => $filters['status']])); ?>
<div class="page-heading"><div><p class="eyebrow">Operations</p><h1><?= esc($title) ?></h1><p>Track every customer booking from intake through redelivery.</p></div><div class="page-actions"><a class="btn btn-outline-secondary" href="<?= site_url('admin/bookings/export.csv' . ($exportQuery !== '' ? '?' . $exportQuery : '')) ?>">Export CSV</a> <a class="btn btn-outline-secondary" href="<?= site_url('admin/bookings/export.xlsx' . ($exportQuery !== '' ? '?' . $exportQuery : '')) ?>">Export Excel</a> <a class="btn btn-primary" href="<?= site_url('admin/bookings/new') ?>">Add booking</a></div></div>
<nav class="settings-nav booking-tabs" aria-label="Booking types"><a class="<?= $bookingType === null ? 'active' : '' ?>" href="<?= site_url('admin/bookings') ?>">All bookings</a><a class="<?= $bookingType === 'summer_storage' ? 'active' : '' ?>" href="<?= site_url('admin/bookings/summer-storage') ?>">Summer Storage</a><a class="<?= $bookingType === 'summer_advantage' ? 'active' : '' ?>" href="<?= site_url('admin/bookings/summer-advantage') ?>">Summer Advantage</a><a href="<?= site_url('admin/schedule') ?>">Calendar schedule</a></nav>
<section class="admin-card"><form class="filter-bar" action="<?= site_url($bookingType === 'summer_storage' ? 'admin/bookings/summer-storage' : ($bookingType === 'summer_advantage' ? 'admin/bookings/summer-advantage' : 'admin/bookings')) ?>" method="get"><div><label class="visually-hidden" for="q">Search bookings</label><input class="form-control" id="q" name="q" type="search" value="<?= esc($filters['q'], 'attr') ?>" placeholder="Reference, customer, or email"></div><div><label class="visually-hidden" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><?php foreach ($statuses as $status): ?><option value="<?= esc($status, 'attr') ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $status))) ?></option><?php endforeach ?></select></div><button class="btn btn-dark" type="submit">Filter</button><?php if (array_filter($filters) !== []): ?><a class="btn btn-link" href="<?= site_url($bookingType === 'summer_storage' ? 'admin/bookings/summer-storage' : ($bookingType === 'summer_advantage' ? 'admin/bookings/summer-advantage' : 'admin/bookings')) ?>">Clear</a><?php endif ?></form>

<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3" style="border-top:1px solid var(--admin-line)">
    <div><strong>Bulk notifications</strong><p class="field-hint mb-0">Sends to every booking matching the filters above.</p></div>
    <form class="d-flex flex-wrap gap-2" action="<?= site_url('admin/bookings/notify/bulk') ?>" method="post" data-confirm="This will email every booking matching your current filters. Continue?" data-confirm-title="Send bulk notification?" data-confirm-button="Yes, send" data-loading-title="Sending notifications" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="all-matching">
        <input type="hidden" name="q" value="<?= esc($filters['q'], 'attr') ?>">
        <input type="hidden" name="status" value="<?= esc($filters['status'], 'attr') ?>">
        <input type="hidden" name="type" value="<?= esc((string) $bookingType, 'attr') ?>">
        <button class="btn btn-outline-primary" type="submit" name="notification_type" value="pickup-needed">Notify: no pickup scheduled</button>
        <button class="btn btn-outline-primary" type="submit" name="notification_type" value="balance-due">Notify: balance due</button>
    </form>
</div>

<?php if ($bookings === []): ?><div class="empty-state"><strong>No matching bookings</strong><p>Adjust the filters or create the first booking.</p><a class="btn btn-primary" href="<?= site_url('admin/bookings/new') ?>">Add booking</a></div><?php else: ?>
<form id="bulkSelectForm" action="<?= site_url('admin/bookings/notify/bulk') ?>" method="post" data-confirm="Send the selected notification to the checked bookings?" data-confirm-title="Send notification?" data-confirm-button="Yes, send" data-loading-title="Sending notifications" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="mode" value="selected">
    <div class="card-body d-flex flex-wrap align-items-center gap-3" style="border-top:1px solid var(--admin-line)">
        <label class="d-flex align-items-center gap-2 mb-0"><input type="checkbox" id="selectAllBookings"> Select all</label>
        <select class="form-select" name="notification_type" style="max-width:280px"><?php foreach ($notificationTypes as $value => $label): ?><option value="<?= esc($value, 'attr') ?>"><?= esc($label) ?></option><?php endforeach ?></select>
        <button class="btn btn-primary" type="submit">Send to selected</button>
    </div>
</form>
<div class="table-responsive">
    <table class="table js-datatable admin-table align-middle mb-0">
        <thead><tr><th></th><th>Reference</th><th>Type</th><th>Customer</th><th>Pickup</th><th>Drop-off</th><th>Total</th><th>Payment</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <?php $paid = $paidByBooking[(int) $booking['id']] ?? 0.0; $balance = max(0, round((float) $booking['total_amount'] - $paid, 2)); ?>
            <tr data-href="<?= site_url('admin/bookings/' . $booking['id']) ?>">
                <td><input type="checkbox" class="js-booking-select" name="booking_ids[]" value="<?= (int) $booking['id'] ?>" form="bulkSelectForm"></td>
                <td><strong><?= esc($booking['reference_code']) ?></strong><small><?= esc(date('M j, Y g:i a', strtotime((string) $booking['created_at']))) ?></small></td>
                <td><span class="role-pill"><?= esc($booking['booking_type'] === 'summer_advantage' ? 'Advantage' : 'Storage') ?></span></td>
                <td><strong><?= esc($booking['customer_name']) ?></strong><small><?= esc($booking['customer_email']) ?></small></td>
                <td><span class="status-pill status-<?= $booking['pickup_date'] ? 'completed' : 'unscheduled' ?>"><?= $booking['pickup_date'] ? 'Done' : 'Not Yet' ?></span><small class="d-block"><?= $booking['pickup_date'] ? esc(date('M j, Y', strtotime((string) $booking['pickup_date']))) : 'Not scheduled' ?></small></td>
                <td><span class="status-pill status-<?= $booking['dropoff_date'] ? 'completed' : 'unscheduled' ?>"><?= $booking['dropoff_date'] ? 'Done' : 'Not Yet' ?></span><small class="d-block"><?= $booking['dropoff_date'] ? esc(date('M j, Y', strtotime((string) $booking['dropoff_date']))) : 'Not scheduled' ?></small></td>
                <td>$<?= number_format((float) $booking['total_amount'], 2) ?></td>
                <td><?php if ($balance < 0.01): ?><span class="status-pill status-completed">Full Paid</span><?php else: ?><span class="status-pill status-pending">Balance Due: $<?= number_format($balance, 2) ?></span><?php endif ?></td>
                <td><span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></td>
                <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/bookings/' . $booking['id']) ?>">Details</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/bookings/' . $booking['id'] . '/edit') ?>">Edit</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/bookings/' . $booking['id'] . '/export.pdf') ?>">PDF</a>
                    <form class="d-inline" action="<?= site_url('admin/bookings/' . $booking['id'] . '/delete') ?>" method="post" data-confirm="Permanently delete booking <?= esc($booking['reference_code'], 'attr') ?>? This cannot be undone." data-confirm-title="Delete booking?" data-confirm-button="Yes, delete" data-confirm-danger="true" data-loading-title="Deleting booking"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?></section>
<?= $this->endSection() ?>
