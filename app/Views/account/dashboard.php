<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div><span class="eyebrow">Customer portal</span><h1 class="h2 mt-2 mb-2">Hello, <?= esc($user['name']) ?></h1><p class="mb-0 text-muted-brand">Review service requests, complete pickup details, and keep your profile current.</p></div>
                <?php if ($bookingOpen): ?><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/new') ?>">Request a service</a><?php endif ?>
            </div>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="portal-card h-100">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><h2 class="h4 mb-0">My bookings</h2><div class="d-flex align-items-center gap-2"><?php if (! $bookingOpen): ?><span class="status-pill status-inactive">New requests paused</span><?php endif ?><?php if ($bookings !== []): ?><a class="btn btn-sm btn-brand-outline" href="<?= site_url('account/bookings') ?>">View all</a><?php endif ?></div></div>
                        <?php if ($bookings === []): ?>
                            <div class="py-4 text-center"><strong class="d-block">No bookings yet</strong><p class="text-muted-brand mt-2 mb-3">Your submitted service requests will appear here.</p><?php if ($bookingOpen): ?><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/new') ?>">Create first request</a><?php else: ?><a class="btn btn-brand-outline" href="<?= site_url('contact') ?>">Contact our team</a><?php endif ?></div>
                        <?php else: ?>
                            <div class="table-responsive"><table class="table admin-table align-middle mb-0"><thead><tr><th>Booking</th><th>Pickup</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php foreach (array_slice($bookings, 0, 3) as $booking): ?><tr><td><strong><?= esc($booking['reference_code']) ?></strong><small class="d-block text-muted-brand"><?= esc($booking['service_name']) ?></small></td><td><?= $booking['pickup_date'] ? esc(date('M j, Y', strtotime((string) $booking['pickup_date']))) : 'Pending' ?><small class="d-block text-muted-brand"><?= $booking['pickup_time'] ? esc(date('g:i A', strtotime((string) $booking['pickup_time']))) : '' ?></small></td><td>$<?= number_format((float) $booking['total_amount'], 2) ?><small class="d-block text-muted-brand"><?= esc(ucfirst($booking['payment_status'])) ?></small></td><td><span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></td><td><a class="btn btn-sm btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id']) ?>">View</a></td></tr><?php endforeach ?></tbody></table></div>
                        <?php endif ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="portal-card h-100">
                        <?php $overviewInitial = mb_strtoupper(mb_substr($user['name'] !== '' ? $user['name'] : 'E', 0, 1)); ?>
                        <div class="account-overview-head">
                            <span class="account-overview-avatar"><?= esc($overviewInitial) ?></span>
                            <div><strong><?= esc($user['name']) ?></strong><span class="status-pill status-active">Active</span></div>
                        </div>
                        <?= view('partials/customer_summary', ['user' => $user]) ?>
                        <dl class="account-details mb-0">
                            <div><dt>Email</dt><dd><?= esc($user['email']) ?></dd></div>
                            <div><dt>Phone</dt><dd><?= esc($user['phone_number'] ?: 'Not provided') ?></dd></div>
                            <div><dt>Default dorm</dt><dd><?= esc($profileDorm['name'] ?? 'Not selected') ?></dd></div>
                            <div><dt>Member since</dt><dd><?= esc(date('F j, Y', strtotime((string) $user['created_at']))) ?></dd></div>
                        </dl>
                        <div class="account-overview-actions">
                            <a class="btn btn-brand-primary" href="<?= site_url('account/profile') ?>">Edit personal details</a>
                            <a class="btn btn-brand-outline" href="<?= site_url('scheduling') ?>">Scheduling details</a>
                            <a class="btn btn-brand-outline" href="<?= site_url('contact') ?>">Contact support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
