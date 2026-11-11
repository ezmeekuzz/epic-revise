<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span><h1 class="h2 mt-2 mb-2">Additional items</h1><p class="mb-0 text-muted-brand">Choose the booking you want to add items to.</p>
            </div>
            <div class="portal-card">
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead><tr><th>Booking</th><th>Type</th><th>Total</th><th>Balance</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?= esc($booking['reference_code']) ?></strong><small class="d-block text-muted-brand"><?= esc(date('M j, Y', strtotime((string) $booking['created_at']))) ?></small></td>
                                <td><?= esc($booking['booking_type'] === 'summer_advantage' ? 'Summer Advantage' : 'Summer Storage') ?></td>
                                <td>$<?= number_format((float) $booking['total_amount'], 2) ?></td>
                                <td>$<?= number_format((float) $booking['balance'], 2) ?></td>
                                <td><a class="btn btn-sm btn-brand-primary" href="<?= site_url('account/bookings/' . $booking['id'] . '/items') ?>">Add items</a></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
