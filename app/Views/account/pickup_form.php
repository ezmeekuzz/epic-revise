<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors') ?? [];
$selectedDormId = (string) old('dorm_id', (string) ($booking['dorm_id'] ?? ''));
?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div><span class="eyebrow">Booking <?= esc($booking['reference_code']) ?></span><h1 class="h2 mt-2 mb-2">Pickup &amp; drop-off</h1><p class="mb-0 text-muted-brand">Add these details now or leave them blank and return when your plans are confirmed.</p></div>
                <a class="btn btn-brand-outline" href="<?= site_url('account/bookings/' . $booking['id']) ?>">Back to booking</a>
            </div>

            <form action="<?= site_url('account/bookings/' . $booking['id'] . '/pickup') ?>" method="post" data-loading-title="Saving pickup and drop-off details" novalidate>
                <?= csrf_field() ?>
                <div class="portal-card mb-4">
                    <h2 class="h5 mb-3">Pickup details</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="customer_phone">Pickup contact phone</label><input class="form-control<?= isset($errors['customer_phone']) ? ' is-invalid' : '' ?>" id="customer_phone" name="customer_phone" type="tel" value="<?= esc(old('customer_phone', $booking['customer_phone'] ?: ($user['phone_number'] ?? '')), 'attr') ?>" maxlength="30" autocomplete="tel" required><?php if (isset($errors['customer_phone'])): ?><div class="invalid-feedback"><?= esc($errors['customer_phone']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="student_id">Student ID</label><input class="form-control<?= isset($errors['student_id']) ? ' is-invalid' : '' ?>" id="student_id" name="student_id" value="<?= esc(old('student_id', $booking['student_id'] ?: ($user['student_id'] ?? '')), 'attr') ?>" maxlength="50" required><?php if (isset($errors['student_id'])): ?><div class="invalid-feedback"><?= esc($errors['student_id']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="dorm_id">Dorm / pickup location <span class="text-muted-brand">(optional)</span></label><select class="form-select<?= isset($errors['dorm_id']) ? ' is-invalid' : '' ?>" id="dorm_id" name="dorm_id"><option value="">Choose later</option><?php foreach ($dorms as $dorm): ?><option value="<?= (int) $dorm['id'] ?>"<?= $selectedDormId === (string) $dorm['id'] ? ' selected' : '' ?>><?= esc($dorm['name']) ?></option><?php endforeach ?></select><?php if (isset($errors['dorm_id'])): ?><div class="invalid-feedback"><?= esc($errors['dorm_id']) ?></div><?php endif ?><div class="form-text">The selected dorm is also recorded as the pickup location.</div></div>
                        <div class="col-md-6"><label class="form-label" for="room_number">Dorm room <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['room_number']) ? ' is-invalid' : '' ?>" id="room_number" name="room_number" value="<?= esc(old('room_number', $booking['room_number'] ?? ''), 'attr') ?>" maxlength="30"><?php if (isset($errors['room_number'])): ?><div class="invalid-feedback"><?= esc($errors['room_number']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="pickup_date">Pickup date <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['pickup_date']) ? ' is-invalid' : '' ?>" id="pickup_date" name="pickup_date" type="date" value="<?= esc(old('pickup_date', $booking['pickup_date'] ?? ''), 'attr') ?>" min="<?= esc($minimumDate, 'attr') ?>"><?php if (isset($errors['pickup_date'])): ?><div class="invalid-feedback"><?= esc($errors['pickup_date']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="pickup_time">Pickup time <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['pickup_time']) ? ' is-invalid' : '' ?>" id="pickup_time" name="pickup_time" type="time" value="<?= esc(old('pickup_time', $booking['pickup_time'] ?? ''), 'attr') ?>"><?php if (isset($errors['pickup_time'])): ?><div class="invalid-feedback"><?= esc($errors['pickup_time']) ?></div><?php endif ?></div>
                    </div>
                    <p class="field-hint mt-3 mb-0">When scheduling a pickup, enter both a date and a time. Leave both blank if you are not ready.</p>
                </div>

                <?php $selectedDropoffDormId = (string) old('dropoff_dorm_id', (string) ($booking['dropoff_dorm_id'] ?? '')); ?>
                <div class="portal-card">
                    <h2 class="h5 mb-3">Drop-off / redelivery details</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="dropoff_dorm_id">Dorm / drop-off location <span class="text-muted-brand">(optional)</span></label><select class="form-select<?= isset($errors['dropoff_dorm_id']) ? ' is-invalid' : '' ?>" id="dropoff_dorm_id" name="dropoff_dorm_id"><option value="">Choose later</option><?php foreach ($dorms as $dorm): ?><option value="<?= (int) $dorm['id'] ?>"<?= $selectedDropoffDormId === (string) $dorm['id'] ? ' selected' : '' ?>><?= esc($dorm['name']) ?></option><?php endforeach ?></select><?php if (isset($errors['dropoff_dorm_id'])): ?><div class="invalid-feedback"><?= esc($errors['dropoff_dorm_id']) ?></div><?php endif ?><div class="form-text">Where your items should be redelivered.</div></div>
                        <div class="col-md-6"><label class="form-label" for="dropoff_room_number">Dorm room <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['dropoff_room_number']) ? ' is-invalid' : '' ?>" id="dropoff_room_number" name="dropoff_room_number" value="<?= esc(old('dropoff_room_number', $booking['dropoff_room_number'] ?? ''), 'attr') ?>" maxlength="30"><?php if (isset($errors['dropoff_room_number'])): ?><div class="invalid-feedback"><?= esc($errors['dropoff_room_number']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="dropoff_date">Drop-off date <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['dropoff_date']) ? ' is-invalid' : '' ?>" id="dropoff_date" name="dropoff_date" type="date" value="<?= esc(old('dropoff_date', $booking['dropoff_date'] ?? ''), 'attr') ?>" min="<?= esc($minimumDate, 'attr') ?>"><?php if (isset($errors['dropoff_date'])): ?><div class="invalid-feedback"><?= esc($errors['dropoff_date']) ?></div><?php endif ?></div>
                        <div class="col-md-6"><label class="form-label" for="dropoff_time">Drop-off time <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['dropoff_time']) ? ' is-invalid' : '' ?>" id="dropoff_time" name="dropoff_time" type="time" value="<?= esc(old('dropoff_time', $booking['dropoff_time'] ?? ''), 'attr') ?>"><?php if (isset($errors['dropoff_time'])): ?><div class="invalid-feedback"><?= esc($errors['dropoff_time']) ?></div><?php endif ?></div>
                    </div>
                    <p class="field-hint mt-3 mb-0">When scheduling a drop-off, enter both a date and a time. Leave both blank if you are not ready.</p>
                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4"><button class="btn btn-brand-primary" type="submit">Save details</button><a class="btn btn-light" href="<?= site_url('account/bookings/' . $booking['id']) ?>">Cancel</a></div>
                </div>
            </form>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
