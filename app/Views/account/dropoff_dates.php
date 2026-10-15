<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span>
                <h1 class="h2 mt-2 mb-2">Manage drop-off dates</h1>
                <p class="mb-0 text-muted-brand">Redelivery windows run <?= esc(date('g:i A', strtotime((string) ($booking['pickup_start_time'] ?? '09:00')))) ?>&ndash;<?= esc(date('g:i A', strtotime((string) ($booking['pickup_end_time'] ?? '17:00')))) ?> in <?= (int) ($booking['slot_duration_minutes'] ?? 30) ?>-minute windows, at least <?= (int) ($booking['minimum_advance_hours'] ?? 24) ?> hours in advance.</p>
            </div>

            <div class="portal-card">
                <?php if ($bookings === []): ?>
                    <div class="py-4 text-center"><strong class="d-block">No bookings yet</strong><p class="text-muted-brand mt-2 mb-0">Once you request a service, you can schedule its drop-off here.</p></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table js-datatable align-middle mb-0">
                            <thead><tr><th>Booking</th><th>Service</th><th>Drop-off date</th><th>Drop-off time</th><th>Dorm / location</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $booking_row): ?>
                                <tr>
                                    <td><strong><?= esc($booking_row['reference_code']) ?></strong></td>
                                    <td><?= esc($booking_row['service_name']) ?></td>
                                    <td><?= $booking_row['dropoff_date'] ? esc(date('M j, Y', strtotime((string) $booking_row['dropoff_date']))) : 'Awaiting schedule' ?></td>
                                    <td><?= $booking_row['dropoff_time'] ? esc(date('g:i A', strtotime((string) $booking_row['dropoff_time']))) : '—' ?></td>
                                    <td><?= esc($booking_row['dropoff_location'] ?: 'Not provided') ?></td>
                                    <td><span class="status-pill status-<?= esc($booking_row['dropoff_status'] ?? 'unscheduled', 'attr') ?>"><?= esc(ucfirst($booking_row['dropoff_status'] ?? 'unscheduled')) ?></span></td>
                                    <td><a class="btn btn-sm btn-brand-outline" href="<?= site_url('account/bookings/' . $booking_row['id'] . '/pickup') ?>">Manage</a></td>
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
