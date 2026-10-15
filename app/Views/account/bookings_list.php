<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div><span class="eyebrow">Customer portal</span><h1 class="h2 mt-2 mb-2">My bookings</h1><p class="mb-0 text-muted-brand">Every Summer Storage and Summer Advantage request you have made with EPIC.</p></div>
                <?php if ($bookingOpen): ?><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/new') ?>">Request a service</a><?php endif ?>
            </div>

            <div class="portal-card">
                <?php if ($bookings === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">No bookings yet</strong><p class="text-muted-brand mt-2 mb-3">Your submitted service requests will appear here.</p><?php if ($bookingOpen): ?><a class="btn btn-brand-primary" href="<?= site_url('account/bookings/new') ?>">Create first request</a><?php else: ?><a class="btn btn-brand-outline" href="<?= site_url('contact') ?>">Contact our team</a><?php endif ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table js-datatable align-middle mb-0">
                            <thead><tr><th>Booking</th><th>Type</th><th>Pickup</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?= esc($booking['reference_code']) ?></strong><small class="d-block text-muted-brand"><?= esc(date('M j, Y', strtotime((string) $booking['created_at']))) ?></small></td>
                                    <td><?= esc($booking['booking_type'] === 'summer_advantage' ? 'Summer Advantage' : 'Summer Storage') ?></td>
                                    <td><?= $booking['pickup_date'] ? esc(date('M j, Y', strtotime((string) $booking['pickup_date']))) : 'Not scheduled' ?></td>
                                    <td>$<?= number_format((float) $booking['total_amount'], 2) ?></td>
                                    <td>$<?= number_format((float) $booking['paid'], 2) ?></td>
                                    <td>$<?= number_format((float) $booking['balance'], 2) ?></td>
                                    <td><span class="status-pill status-<?= esc($booking['status'], 'attr') ?>"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></td>
                                    <td><a class="btn btn-sm btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id']) ?>">View</a></td>
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
