<?php
/**
 * One booking form for both modes:
 * - logged in ($guest false): account layout, name/email come from the account;
 * - no account ($guest true): public site layout, name/email are typed in.
 * Pricing, items, leaving campus and the shared validation are identical in both.
 */
$guest = $guest ?? false;
?>
<?= $this->extend($layout ?? 'layouts/account') ?>

<?= $this->section('head') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/booking-flow.css') ?>?v=<?= (int) filemtime(FCPATH . 'assets/css/booking-flow.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors') ?? [];
$bookingOpen = ($booking['bookings_enabled'] ?? '0') === '1';
$slotMinutes = (int) ($booking['slot_duration_minutes'] ?? 30);
$lastSlot = date('H:i', strtotime((string) ($booking['pickup_end_time'] ?? '17:00')) - ($slotMinutes * 60));
$bookingType = old('booking_type', $selectedBookingType ?? 'summer_storage');
// Arrived from a specific service: show only that service so nothing else is offered.
$lockedService = ! empty($lockedService) || old('booking_type') !== null && ! empty(old('locked_service'));
$oldItems = old('items', []);
if (! is_array($oldItems)) {
    $oldItems = [];
}
$processingConfig = [
    'catalog' => $itemCatalog,
    'selectedItems' => array_values($oldItems),
    'bookingType' => $bookingType,
    'prices' => $pricing,
    'availabilityUrl' => $availabilityUrl ?? site_url('book/availability'),
];
$field = static function (string $name, string $label, array $errors, string $value, array $attributes = [], bool $optional = false): string {
    $attributeHtml = '';
    foreach ($attributes as $attribute => $attributeValue) {
        $attributeHtml .= ' ' . $attribute . ($attributeValue === true ? '' : '="' . esc((string) $attributeValue, 'attr') . '"');
    }
    $html = '<label class="form-label" for="' . esc($name, 'attr') . '">' . esc($label) . ($optional ? ' <span class="text-muted-brand">(optional)</span>' : '') . '</label>';
    $html .= '<input class="form-control' . (isset($errors[$name]) ? ' is-invalid' : '') . '" id="' . esc($name, 'attr') . '" name="' . esc($name, 'attr') . '" value="' . esc($value, 'attr') . '"' . $attributeHtml . '>';
    if (isset($errors[$name])) {
        $html .= '<div class="invalid-feedback">' . esc($errors[$name]) . '</div>';
    }

    return $html;
};
?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4">
                <span class="eyebrow"><?= $guest ? 'Book online' : 'Customer portal' ?></span>
                <h1 class="h2 mt-2 mb-2"><?= $guest ? 'Book your storage' : 'Process your service' ?></h1>
                <p class="mb-0 text-muted-brand"><?= $guest ? 'No account needed. Choose your service, enter your details and pay securely. Leaving Campus details can be added later.' : 'Configure your storage service and submit it now. Leaving Campus information can be added later.' ?></p>
                <?php if ($guest): ?><p class="small mt-2 mb-0">Already have an account? <a href="<?= site_url('login') ?>">Log in</a> to fill in your details automatically.</p><?php endif ?>
            </div>

            <?php if (! $bookingOpen): ?>
                <div class="portal-card">
                    <span class="status-pill status-inactive">Requests paused</span>
                    <h2 class="h4 mt-3">Online scheduling is unavailable</h2>
                    <p class="text-muted-brand"><?= esc($booking['booking_unavailable_message'] ?? 'Please contact our team for help.') ?></p>
                    <a class="btn btn-brand-primary" href="<?= site_url('contact') ?>">Contact our team</a>
                </div>
            <?php else: ?>
                <form action="<?= esc($formAction ?? site_url('account/bookings'), 'attr') ?>" method="post" data-booking-processing-form data-loading-title="Preparing your payment" novalidate>
                    <?= csrf_field() ?>
                    <div class="booking-processing-grid">
                        <div class="booking-processing-main">
                            <section class="portal-card mb-4" aria-labelledby="serviceHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">1</span>
                                    <div><h2 class="h4 mb-1" id="serviceHeading">Choose your service</h2><p class="text-muted-brand mb-0">Each service has its own processing and pricing rules.</p></div>
                                </div>
                                <div class="service-choice-grid mt-4">
                                    <?php if (! $lockedService || $bookingType === 'summer_storage'): ?><label class="service-choice<?= $bookingType === 'summer_storage' ? ' is-selected' : '' ?>">
                                        <input type="radio" name="booking_type" value="summer_storage"<?= $bookingType === 'summer_storage' ? ' checked' : '' ?>>
                                        <span class="service-choice-copy"><strong>Summer Storage (April to August)</strong><small>This offering is for current HPU students.</small><b>From $<?= esc($pricing['summer_storage_base_price']) ?></b></span>
                                    </label><?php endif ?>
                                    <?php if (! $lockedService || $bookingType === 'summer_advantage'): ?><label class="service-choice<?= $bookingType === 'summer_advantage' ? ' is-selected' : '' ?>">
                                        <input type="radio" name="booking_type" value="summer_advantage"<?= $bookingType === 'summer_advantage' ? ' checked' : '' ?>>
                                        <span class="service-choice-copy"><strong>Summer Advantage (July to August)</strong><small>For incoming HPU freshmen attending the Summer Advantage Program.</small><b>$<?= esc($pricing['summer_advantage_box_price']) ?> per box</b></span>
                                    </label><?php endif ?>
                                    <?php if ($lockedService): ?><input type="hidden" name="locked_service" value="1"><?php endif ?>
                                </div>
                                <?php if (isset($errors['booking_type'])): ?><div class="text-danger small mt-2"><?= esc($errors['booking_type']) ?></div><?php endif ?>
                            </section>

                            <section class="portal-card mb-4" data-service-panel="summer_storage"<?= $bookingType === 'summer_storage' ? '' : ' hidden' ?> aria-labelledby="storageHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">2</span>
                                    <div><h2 class="h4 mb-1" id="storageHeading">Summer Storage options</h2><p class="text-muted-brand mb-0">Your base package includes five 18 × 18 × 24-inch boxes, packing tape, leaving campus, storage, and redelivery.</p></div>
                                </div>

                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label class="form-label" for="additional_box_quantity">Additional boxes</label>
                                        <div class="quantity-stepper" data-quantity-stepper>
                                            <button type="button" data-quantity-action="decrement" aria-label="Decrease additional Summer Storage boxes"><span aria-hidden="true">−</span></button>
                                            <input class="form-control<?= isset($errors['box_quantity']) ? ' is-invalid' : '' ?>" id="additional_box_quantity" name="additional_box_quantity" type="number" min="0" max="100" step="1" value="<?= esc(old('additional_box_quantity', '0'), 'attr') ?>" inputmode="numeric" aria-label="Additional Summer Storage boxes">
                                            <button type="button" data-quantity-action="increment" aria-label="Increase additional Summer Storage boxes"><span aria-hidden="true">+</span></button>
                                        </div>
                                        <?php if (isset($errors['box_quantity'])): ?><div class="invalid-feedback d-block"><?= esc($errors['box_quantity']) ?></div><?php endif ?>
                                        <p class="field-hint">$<?= esc($pricing['summer_storage_additional_box_price']) ?> for each box beyond the five included boxes—the same per-box price as Summer Advantage.</p>
                                    </div>
                                </div>

                                <div class="service-subsection mt-4">
                                    <div class="mb-3"><h3 class="h5 mb-1">Additional storage items</h3><p class="text-muted-brand small mb-0">Search for an item, or click the search box to browse them all. Prices are per item.</p></div>
                                    <?php if ($itemCatalog === []): ?>
                                        <div class="booking-empty-state">No storage items are available. Please contact EPIC support.</div>
                                    <?php else: ?>
                                        <div class="item-search" data-item-search>
                                            <label class="visually-hidden" for="item_search">Search storage items</label>
                                            <div class="item-search-box">
                                                <span class="item-search-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                                                <input class="form-control" id="item_search" type="search" placeholder="Search items, e.g. mini fridge, rug, bike" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="item_search_results">
                                            </div>
                                            <ul class="item-search-results" id="item_search_results" role="listbox" aria-label="Matching items" hidden></ul>
                                        </div>
                                        <h4 class="item-selected-heading mt-4">Your items</h4>
                                        <div class="booking-item-list" data-selected-list aria-live="polite"></div>
                                        <div data-selected-items hidden></div>
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

                            <section class="portal-card mb-4" aria-labelledby="detailsHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">3</span>
                                    <div><h2 class="h4 mb-1" id="detailsHeading">Your details</h2><p class="text-muted-brand mb-0"><?= $guest ? 'We email your receipt and booking details to this address.' : 'Saved to your profile, so you only enter them once.' ?></p></div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <?php if ($guest): ?>
                                        <div class="col-md-6"><?= $field('customer_name', 'Student name', $errors, (string) old('customer_name'), ['autocomplete' => 'name', 'maxlength' => 100, 'minlength' => 2, 'required' => true]) ?></div>
                                        <div class="col-md-6"><?= $field('customer_email', 'Email address', $errors, (string) old('customer_email'), ['type' => 'email', 'autocomplete' => 'email', 'maxlength' => 254, 'required' => true]) ?></div>
                                    <?php else: ?>
                                        <div class="col-md-6"><label class="form-label" for="customer_name">Customer</label><input class="form-control" id="customer_name" value="<?= esc($user['name'] ?? '', 'attr') ?>" disabled></div>
                                        <div class="col-md-6"><label class="form-label" for="customer_email">Email</label><input class="form-control" id="customer_email" value="<?= esc($user['email'] ?? '', 'attr') ?>" disabled></div>
                                    <?php endif ?>
                                    <div class="col-md-6"><?= $field('customer_phone', 'Student phone', $errors, (string) old('customer_phone', $user['phone_number'] ?? ''), ['type' => 'tel', 'autocomplete' => 'tel', 'maxlength' => 30, 'required' => true]) ?></div>
                                    <div class="col-md-6"><?= $field('student_id', 'Student ID', $errors, (string) old('student_id', $user['student_id'] ?? ''), ['maxlength' => 50, 'autocomplete' => 'off', 'required' => true]) ?></div>
                                    <div class="col-md-6"><?= $field('parent_phone_number', 'Parent or guardian phone', $errors, (string) old('parent_phone_number', $user['parent_phone_number'] ?? ''), ['type' => 'tel', 'maxlength' => 30, 'autocomplete' => 'off', 'required' => true]) ?></div>
                                    <div class="col-md-6"><?= $field('parent_email_address', 'Parent or guardian email', $errors, (string) old('parent_email_address', $user['parent_email_address'] ?? ''), ['type' => 'email', 'maxlength' => 254, 'autocomplete' => 'off', 'required' => true]) ?></div>
                                </div>
                            </section>

                            <section class="portal-card" aria-labelledby="pickupHeading">
                                <div class="portal-section-heading">
                                    <span class="booking-step">4</span>
                                    <div><h2 class="h4 mb-1" id="pickupHeading">Leaving Campus details <span class="text-muted-brand fs-6">(optional)</span></h2><p class="text-muted-brand mb-0">Leave these blank and add them later, or pick a time now. Pickups run <?= esc(date('g:i A', strtotime((string) ($booking['pickup_start_time'] ?? '09:00')))) ?>–<?= esc(date('g:i A', strtotime((string) ($booking['pickup_end_time'] ?? '17:00')))) ?> <?= esc($timezone) ?>.</p></div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6"><label class="form-label" for="dorm_id">Dorm / leaving campus location</label><?php $dormId = (string) old('dorm_id', (string) ($user['dorm_id'] ?? '')); ?><select class="form-select<?= isset($errors['dorm_id']) ? ' is-invalid' : '' ?>" id="dorm_id" name="dorm_id"><option value="">Choose later</option><?php foreach ($dorms as $dorm): ?><option value="<?= (int) $dorm['id'] ?>"<?= $dormId === (string) $dorm['id'] ? ' selected' : '' ?>><?= esc($dorm['name']) ?></option><?php endforeach ?></select><?php if (isset($errors['dorm_id'])): ?><div class="invalid-feedback"><?= esc($errors['dorm_id']) ?></div><?php endif ?></div>
                                    <div class="col-md-6"><?= $field('room_number', 'Dorm room', $errors, (string) old('room_number', $user['dorm_room_number'] ?? ''), ['maxlength' => 30]) ?></div>
                                    <div class="col-md-6"><?= $field('pickup_date', 'Leaving Campus', $errors, (string) old('pickup_date'), ['type' => 'date', 'min' => $minimumDate, 'data-slot-date' => true]) ?></div>
                                    <div class="col-12" data-slot-picker>
                                        <div data-slot-fallback>
                                            <?= $field('pickup_time', 'Leaving Campus time', $errors, (string) old('pickup_time'), ['type' => 'time', 'min' => $booking['pickup_start_time'] ?? '09:00', 'max' => $lastSlot, 'step' => $slotMinutes * 60]) ?>
                                        </div>
                                        <div class="slot-picker" data-slot-buttons hidden>
                                            <span class="form-label d-block">Leaving Campus time</span>
                                            <p class="field-hint mb-2" data-slot-message>Choose a leaving campus to see available times.</p>
                                            <div class="slot-grid" data-slot-grid role="group" aria-label="Available leaving campus times"></div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <?php if (! empty($square['ready'])): ?>
                            <section class="portal-card mb-4" aria-labelledby="checkoutPaymentHeading" data-checkout-payment>
                                <div class="portal-section-heading">
                                    <span class="booking-step">5</span>
                                    <div><h2 class="h4 mb-1" id="checkoutPaymentHeading">Payment</h2><p class="text-muted-brand mb-0">Pay now to finish in one step, or save your order and pay later with a link we email you.</p></div>
                                </div>
                                <div class="service-choice-grid mt-4">
                                    <label class="service-choice is-selected"><input type="radio" name="payment_option" value="now" checked><span class="service-choice-copy"><strong>Pay now by card</strong><small>Secure payment through Square.</small></span></label>
                                    <label class="service-choice"><input type="radio" name="payment_option" value="later"><span class="service-choice-copy"><strong>Pay later</strong><small>Your order is saved. We email you a payment link.</small></span></label>
                                </div>
                                <div class="mt-4" data-checkout-card-wrap>
                                    <div class="wallet-buttons" data-checkout-wallets hidden>
                                        <div class="apple-pay-button" id="checkout-apple-pay-button" role="button" tabindex="0" aria-label="Pay with Apple Pay" hidden></div>
                                        <div id="checkout-google-pay-button" hidden></div>
                                        <div class="wallet-divider"><span>or pay by card</span></div>
                                    </div>
                                    <div id="checkout-card-container" class="square-card-container" aria-label="Secure Square card fields"></div>
                                    <div class="payment-inline-status mt-3" data-checkout-status role="status" aria-live="polite"></div>
                                    <p class="field-hint mb-0">Card details are collected securely by Square and are not stored by EPIC.</p>
                                </div>
                                <input type="hidden" name="square_token" value="" data-checkout-token>
                            </section>
                            <?php endif ?>
                        </div>

                        <aside class="portal-card booking-price-summary" aria-labelledby="estimateHeading">
                            <p class="eyebrow">Live estimate</p><h2 class="h4" id="estimateHeading">Service summary</h2>
                            <div class="booking-summary-lines" data-pricing-summary></div>
                            <div class="booking-summary-total"><span>Estimated total</span><strong data-estimated-total>$0.00</strong></div>
                            <p class="field-hint">Final pricing is calculated and saved securely on the server when you submit.</p>
                            <div class="form-check mt-3 mb-0">
                                <input class="form-check-input<?= isset($errors['terms']) ? ' is-invalid' : '' ?>" id="order_terms" name="terms" type="checkbox" value="1"<?= old('terms') === '1' ? ' checked' : '' ?> required>
                                <label class="form-check-label" for="order_terms">I agree to the <a href="<?= site_url('terms') ?>" target="_blank" rel="noopener">Terms and Conditions</a> for this order.</label>
                                <?php if (isset($errors['terms'])): ?><div class="invalid-feedback"><?= esc($errors['terms']) ?></div><?php endif ?>
                            </div>
                            <div class="d-grid gap-2 mt-3"><button class="btn btn-brand-primary" type="submit" data-checkout-submit><?= ! empty($square['ready']) ? 'Place order' : 'Continue to payment' ?></button><a class="btn btn-light" href="<?= site_url($guest ? 'services' : 'account') ?>">Cancel</a></div>
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
<?php if (! empty($square['ready'])): ?>
<script id="checkoutSquareConfig" type="application/json"><?= json_encode(['applicationId' => $square['applicationId'], 'locationId' => $square['locationId'], 'currency' => $square['currency']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= esc($square['sdkUrl'], 'attr') ?>"></script>
<script src="<?= base_url('assets/js/booking-checkout.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/booking-checkout.js') ?>"></script>
<?php endif ?>
<script src="<?= base_url('assets/js/booking-processing.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/booking-processing.js') ?>"></script>
<?= $this->endSection() ?>
