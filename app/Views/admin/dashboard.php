<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div><p class="eyebrow">Business overview</p><h1>Dashboard</h1><p>Current activity across bookings, customers, inventory, and payments.</p></div>
    <div class="page-actions"><a class="btn btn-outline-primary" href="<?= site_url('admin/items/new') ?>">Add item</a><a class="btn btn-primary" href="<?= site_url('admin/bookings/new') ?>">Add booking</a></div>
</div>

<div class="stats-grid">
    <article class="stat-card metric-blue"><span>Total bookings</span><strong><?= number_format($totalBookings) ?></strong><small><?= number_format($pendingBookings) ?> awaiting confirmation</small><span class="metric-icon" aria-hidden="true">▣</span></article>
    <article class="stat-card metric-green"><span>Net payments</span><strong>$<?= number_format($completedRevenue, 2) ?></strong><small>Completed charges less refunds</small><span class="metric-icon" aria-hidden="true">$</span></article>
    <article class="stat-card metric-purple"><span>Customer accounts</span><strong><?= number_format($activeCustomers) ?></strong><small><?= number_format($totalUsers) ?> total user accounts</small><span class="metric-icon" aria-hidden="true">◎</span></article>
    <article class="stat-card metric-yellow"><span>Active items</span><strong><?= number_format($activeItems) ?></strong><small><?= number_format($pendingPayments) ?> pending payments</small><span class="metric-icon" aria-hidden="true">◇</span></article>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <section class="dashboard-card h-100">
            <div class="card-heading"><div><h2>Recent bookings</h2><p>Latest customer storage activity.</p></div><a href="<?= site_url('admin/bookings') ?>">View all</a></div>
            <?php if ($recentBookings === []): ?>
                <div class="empty-state"><strong>No bookings yet</strong><p>Create a booking to begin tracking storage operations.</p><a class="btn btn-primary" href="<?= site_url('admin/bookings/new') ?>">Add booking</a></div>
            <?php else: ?>
                <div class="table-responsive"><table class="table js-datatable admin-table align-middle mb-0"><thead><tr><th>Booking</th><th>Customer</th><th>Service</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
                <?php foreach ($recentBookings as $booking): ?><tr><td><strong><?= esc($booking['reference_code']) ?></strong><small><?= esc(date('M j, Y', strtotime((string) $booking['created_at']))) ?></small></td><td><?= esc($booking['customer_name']) ?></td><td><?= esc($booking['service_name']) ?></td><td>$<?= number_format((float) $booking['total_amount'], 2) ?></td><td><span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/bookings/' . $booking['id']) ?>">View</a></td></tr><?php endforeach ?>
                </tbody></table></div>
            <?php endif ?>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="dashboard-card h-100">
            <div class="card-heading"><div><h2>Recent users</h2><p>Newest customer and admin accounts.</p></div><a href="<?= site_url('admin/users') ?>">Manage</a></div>
            <?php if ($recentUsers === []): ?><div class="empty-state"><strong>No accounts</strong><p>Registered users appear here.</p></div><?php else: ?>
                <?php foreach ($recentUsers as $user): ?><div class="p-3 border-bottom d-flex align-items-center gap-3"><span class="admin-avatar"><?= esc(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><div class="overflow-hidden"><strong class="d-block text-truncate small"><?= esc($user['name']) ?></strong><span class="d-block text-truncate text-secondary small"><?= esc($user['email']) ?></span></div><span class="role-pill ms-auto"><?= esc(ucfirst($user['role'])) ?></span></div><?php endforeach ?>
            <?php endif ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
