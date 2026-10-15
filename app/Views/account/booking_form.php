<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors') ?? [];
$bookingOpen = ($booking['bookings_enabled'] ?? '0') === '1';
$slotMinutes = (int) ($booking['slot_duration_minutes'] ?? 30);
$lastSlot = date('H:i', strtotime((string) ($booking['pickup_end_time'] ?? '17:00')) - ($slotMinutes * 60));
$bookingType = old('booking_type', $selectedBookingType ?? 'summer_storage');
$oldItems = old('items', []);
if (! is_array($oldItems)) {
    $oldItems = [];
}
$processingConfig = [
    'catalog' => $itemCatalog,
    'selectedItems' => $oldItems,
    'bookingType' => $bookingType,
    'summerSchool' => old('is_summer_school', '0') === '1',
    'studyAbroad' => old('is_study_abroad', '0') === '1',
    'storageBoxes' => max(0, (int) old('additional_box_quantity', '0')),
    'advantageBoxes' => max(1, (int) old('box_quantity', '1')),
    'prices' => $pricing,
];
?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow">Customer portal</span>
                <h1 class="h2 mt-2 mb-2">Process your service</h1>
                <p class="mb-0 text-muted-brand">Configure your storage service and submit it now. Pickup information can be added later.</p>
            </div>

            <?php if (! $bookingOpen): ?>
                <div class="portal-card">
                    <span class="status-pill status-inactive">Requests paused</span>
                    <h2 class="h4 mt-3">Online scheduling is unavailable</h2>
                    <p class="text-muted-brand"><?= esc($booking['booking_unavailable_message'] ?? 'Please contact our team for help.') ?></p>
                    <a class="btn btn-brand-primary" href="<?= site_url('contact') ?>">Contact our team</a>
                </div>
            <?php else: ?>
                <form action="<?= site_url('account/bookings') ?>" method="post" data-booking-processing-form data-confirm="Save this service request and continue to secure payment?" data-confirm-title="Continue to payment?" data-confirm-button="Yes, continue" data-loading-title="Preparing your payment" novalidate>
                    <?= csrf_field() ?>
                <div class="form-check mb-4"><input class="form-check-input" id="order_terms" name="terms" type="checkbox" value="1" required><label class="form-check-label" for="order_terms">I agree to the <a href="<?= site_url('terms') ?>" target="_blank" rel="noopener">Terms and Conditions</a> for this order.</label></div>
                    <div class="booking-processing-grid">
                        <div class="booking-processing-main">
                            <section class="portal-card mb-4" aria-labelledby="serviceHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">1</span>
                                    <div><h2 class="h4 mb-1" id="serviceHeading">Choose your service</h2><p class="text-muted-brand mb-0">Each service has its own processing and pricing rules.</p></div>
                                </div>
                                <div class="service-choice-grid mt-4">
                                    <label class="service-choice<?= $bookingType === 'summer_storage' ? ' is-selected' : '' ?>">
                                        <input type="radio" name="booking_type" value="summer_storage"<?= $bookingType === 'summer_storage' ? ' checked' : '' ?>>
                                        <span class="service-choice-copy"><strong>Summer Storage</strong><small>Five-box base package, optional boxes and storage items.</small><b>From $<?= esc($pricing['summer_storage_base_price']) ?></b></span>
                                    </label>
                                    <label class="service-choice<?= $bookingType === 'summer_advantage' ? ' is-selected' : '' ?>">
                                        <input type="radio" name="booking_type" value="summer_advantage"<?= $bookingType === 'summer_advantage' ? ' checked' : '' ?>>
                                        <span class="service-choice-copy"><strong>Summer Advantage</strong><small>Choose only the number of boxes you need.</small><b>$<?= esc($pricing['summer_advantage_box_price']) ?> per box</b></span>
                                    </label>
                                </div>
                                <?php if (isset($errors['booking_type'])): ?><div class="text-danger small mt-2"><?= esc($errors['booking_type']) ?></div><?php endif ?>
                            </section>

                            <section class="portal-card mb-4" data-service-panel="summer_storage"<?= $bookingType === 'summer_storage' ? '' : ' hidden' ?> aria-labelledby="storageHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">2</span>
                                    <div><h2 class="h4 mb-1" id="storageHeading">Summer Storage options</h2><p class="text-muted-brand mb-0">Your base package includes five 18 × 18 × 24-inch boxes, packing tape, pickup, storage, and redelivery.</p></div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label" for="additional_box_quantity">Additional boxes</label>
                                        <div class="quantity-stepper" data-quantity-stepper>
                                            <button type="button" data-quantity-action="decrement" aria-label="Decrease additional Summer Storage boxes"><span aria-hidden="true">−</span></button>
                                            <input class="form-control<?= isset($errors['box_quantity']) ? ' is-invalid' : '' ?>" id="additional_box_quantity" name="additional_box_quantity" type="number" min="0" max="100" step="1" value="<?= esc(old('additional_box_quantity', '0'), 'attr') ?>" inputmode="numeric" aria-label="Additional Summer Storage boxes">
                                            <button type="button" data-quantity-action="increment" aria-label="Increase additional Summer Storage boxes"><span aria-hidden="true">+</span></button>
                                        </div>
                                        <?php if (isset($errors['box_quantity'])): ?><div class="invalid-feedback"><?= esc($errors['box_quantity']) ?></div><?php endif ?>
                                        <p class="field-hint">$<?= esc($pricing['summer_storage_additional_box_price']) ?> for each box beyond the five included boxes—the same per-box price as Summer Advantage.</p>
                                    </div>
                                </div>

                                <div class="service-subsection mt-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3"><div><h3 class="h5 mb-1">Additional storage items</h3><p class="text-muted-brand small mb-0">Select an item, size, and quantity, then add it to your booking.</p></div></div>
                                    <?php if ($itemCatalog === []): ?>
                                        <div class="booking-empty-state">No storage items are available. Please contact EPIC support.</div>
                                    <?php else: ?>
                                        <div class="item-builder">
                                            <div><label class="form-label" for="storage_item">Item</label><select class="form-select" id="storage_item"><option value="">Select an item</option><?php foreach ($itemCatalog as $item): ?><option value="<?= (int) $item['id'] ?>"><?= esc($item['name']) ?></option><?php endforeach ?></select></div>
                                            <div><label class="form-label" for="storage_item_size">Size</label><select class="form-select" id="storage_item_size" disabled><option value="">Choose an item first</option></select></div>
                                            <div><label class="form-label" for="storage_item_quantity">Quantity</label><div class="quantity-stepper" data-quantity-stepper><button type="button" data-quantity-action="decrement" aria-label="Decrease selected item quantity"><span aria-hidden="true">−</span></button><input class="form-control" id="storage_item_quantity" type="number" min="1" max="100" step="1" value="1" inputmode="numeric" aria-label="Selected item quantity"><button type="button" data-quantity-action="increment" aria-label="Increase selected item quantity"><span aria-hidden="true">+</span></button></div></div>
                                            <button class="btn btn-brand-outline" type="button" data-add-storage-item>Add item</button>
                                        </div>
                                        <div class="booking-item-list mt-3" data-selected-items><div class="booking-empty-state">No additional items selected.</div></div>
                                    <?php endif ?>
                                </div>

                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <fieldset class="binary-choice"><legend>Are you doing summer school?</legend><p>$<?= esc($pricing['summer_school_fee']) ?> is added when selected.</p><div><label><input type="radio" name="is_summer_school" value="1"<?= old('is_summer_school', '0') === '1' ? ' checked' : '' ?>> Yes</label><label><input type="radio" name="is_summer_school" value="0"<?= old('is_summer_school', '0') !== '1' ? ' checked' : '' ?>> No</label></div></fieldset>
                                    </div>
                                    <div class="col-md-6">
                                        <fieldset class="binary-choice"><legend>Are you studying abroad?</legend><p>Your complete Summer Storage subtotal is doubled for second-semester storage.</p><div><label><input type="radio" name="is_study_abroad" value="1"<?= old('is_study_abroad', '0') === '1' ? ' checked' : '' ?>> Yes</label><label><input type="radio" name="is_study_abroad" value="0"<?= old('is_study_abroad', '0') !== '1' ? ' checked' : '' ?>> No</label></div></fieldset>
                                    </div>
                                </div>
                            </section>

                            <section class="portal-card mb-4" data-service-panel="summer_advantage"<?= $bookingType === 'summer_advantage' ? '' : ' hidden' ?> aria-labelledby="advantageHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">2</span>
                                    <div><h2 class="h4 mb-1" id="advantageHeading">Summer Advantage boxes</h2><p class="text-muted-brand mb-0">Summer Advantage requires only your box quantity. Additional items and Summer Storage options do not apply.</p></div>
                                </div>
                                <div class="row g-3 mt-2"><div class="col-md-6"><label class="form-label" for="box_quantity">Number of boxes</label><div class="quantity-stepper" data-quantity-stepper><button type="button" data-quantity-action="decrement" aria-label="Decrease Summer Advantage boxes"><span aria-hidden="true">−</span></button><input class="form-control<?= isset($errors['box_quantity']) ? ' is-invalid' : '' ?>" id="box_quantity" name="box_quantity" type="number" min="1" max="100" step="1" value="<?= esc(old('box_quantity', '1'), 'attr') ?>" inputmode="numeric" aria-label="Summer Advantage boxes"<?= $bookingType === 'summer_advantage' ? ' required' : '' ?>><button type="button" data-quantity-action="increment" aria-label="Increase Summer Advantage boxes"><span aria-hidden="true">+</span></button></div><?php if (isset($errors['box_quantity'])): ?><div class="invalid-feedback d-block"><?= esc($errors['box_quantity']) ?></div><?php endif ?><p class="field-hint">$<?= esc($pricing['summer_advantage_box_price']) ?> per box.</p></div></div>
                            </section>

                            <section class="portal-card" aria-labelledby="pickupHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">3</span>
                                    <div><h2 class="h4 mb-1" id="pickupHeading">Contact and optional pickup details</h2><p class="text-muted-brand mb-0">You may leave every pickup field blank and complete it from your booking later. When scheduling now, enter both a date and time during <?= esc(date('g:i A', strtotime((string) ($booking['pickup_start_time'] ?? '09:00')))) ?>–<?= esc(date('g:i A', strtotime((string) ($booking['pickup_end_time'] ?? '17:00')))) ?> <?= esc($timezone) ?>.</p></div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6"><label class="form-label" for="customer_name">Customer</label><input class="form-control" id="customer_name" value="<?= esc($user['name'], 'attr') ?>" disabled></div>
                                    <div class="col-md-6"><label class="form-label" for="customer_email">Email</label><input class="form-control" id="customer_email" value="<?= esc($user['email'], 'attr') ?>" disabled></div>
                                    <div class="col-md-6"><label class="form-label" for="customer_phone">Phone</label><input class="form-control<?= isset($errors['customer_phone']) ? ' is-invalid' : '' ?>" id="customer_phone" name="customer_phone" type="tel" value="<?= esc(old('customer_phone', $user['phone_number'] ?? ''), 'attr') ?>" maxlength="30" autocomplete="tel" required><?php if (isset($errors['customer_phone'])): ?><div class="invalid-feedback"><?= esc($errors['customer_phone']) ?></div><?php endif ?></div>
                                    <div class="col-md-6"><label class="form-label" for="student_id">Student ID</label><input class="form-control<?= isset($errors['student_id']) ? ' is-invalid' : '' ?>" id="student_id" name="student_id" value="<?= esc(old('student_id', $user['student_id'] ?? ''), 'attr') ?>" maxlength="50" required><?php if (isset($errors['student_id'])): ?><div class="invalid-feedback"><?= esc($errors['student_id']) ?></div><?php endif ?></div>
                                    <div class="col-md-6"><label class="form-label" for="dorm_id">Dorm / pickup location <span class="text-muted-brand">(optional)</span></label><?php $dormId = (string) old('dorm_id', (string) ($user['dorm_id'] ?? '')); ?><select class="form-select<?= isset($errors['dorm_id']) ? ' is-invalid' : '' ?>" id="dorm_id" name="dorm_id"><option value="">Choose later</option><?php foreach ($dorms as $dorm): ?><option value="<?= (int) $dorm['id'] ?>"<?= $dormId === (string) $dorm['id'] ? ' selected' : '' ?>><?= esc($dorm['name']) ?></option><?php endforeach ?></select><?php if (isset($errors['dorm_id'])): ?><div class="invalid-feedback"><?= esc($errors['dorm_id']) ?></div><?php endif ?><div class="form-text">The selected dorm is saved as the pickup location.</div></div>
                                    <div class="col-md-6"><label class="form-label" for="room_number">Dorm room <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['room_number']) ? ' is-invalid' : '' ?>" id="room_number" name="room_number" value="<?= esc(old('room_number', $user['dorm_room_number'] ?? ''), 'attr') ?>" maxlength="30"><?php if (isset($errors['room_number'])): ?><div class="invalid-feedback"><?= esc($errors['room_number']) ?></div><?php endif ?></div>
                                    <div class="col-md-6"><label class="form-label" for="pickup_date">Preferred pickup date <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['pickup_date']) ? ' is-invalid' : '' ?>" id="pickup_date" name="pickup_date" type="date" value="<?= esc(old('pickup_date'), 'attr') ?>" min="<?= esc($minimumDate, 'attr') ?>"><?php if (isset($errors['pickup_date'])): ?><div class="invalid-feedback"><?= esc($errors['pickup_date']) ?></div><?php endif ?></div>
                                    <div class="col-md-6"><label class="form-label" for="pickup_time">Preferred pickup time <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['pickup_time']) ? ' is-invalid' : '' ?>" id="pickup_time" name="pickup_time" type="time" value="<?= esc(old('pickup_time'), 'attr') ?>" min="<?= esc($booking['pickup_start_time'] ?? '09:00', 'attr') ?>" max="<?= esc($lastSlot, 'attr') ?>" step="<?= $slotMinutes * 60 ?>"><?php if (isset($errors['pickup_time'])): ?><div class="invalid-feedback"><?= esc($errors['pickup_time']) ?></div><?php endif ?></div>
                                </div>
                            </section>
                        </div>

                        <aside class="portal-card booking-price-summary" aria-labelledby="estimateHeading">
                            <p class="eyebrow">Live estimate</p><h2 class="h4" id="estimateHeading">Service summary</h2>
                            <div class="booking-summary-lines" data-pricing-summary></div>
                            <div class="booking-summary-total"><span>Estimated total</span><strong data-estimated-total>$0.00</strong></div>
                            <p class="field-hint">Final pricing is calculated and saved securely on the server when you submit.</p>
                            <div class="d-grid gap-2 mt-4"><button class="btn btn-brand-primary" type="submit">Continue</button><a class="btn btn-light" href="<?= site_url('account') ?>">Cancel</a></div>
                        </aside>
                    </div>
                </form>
                <script id="bookingProcessingConfig" type="application/json"><?= json_encode($processingConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
            <?php endif ?>
        </div>
    </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/booking-processing.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/booking-processing.js') ?>"></script>
<?= $this->endSection() ?>
