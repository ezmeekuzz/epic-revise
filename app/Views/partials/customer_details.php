<?php
$detailGroups = [
    'Student details' => ['student_id', 'phone_number'],
    'Parent or guardian' => ['parent_phone_number', 'parent_email_address'],
    'Shipping address' => ['shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip'],
];
?>
<p class="text-muted-brand mt-4">Student, parent, and shipping details are required.</p>
<?php foreach ($detailGroups as $groupLabel => $groupFields): ?>
<fieldset class="mb-4">
    <legend class="h5"><?= esc($groupLabel) ?></legend>
    <?php if ($groupLabel === 'Shipping address'): ?>
        <p class="text-muted-brand small">For items shipped home, enter the complete destination address, including any apartment number. Review this address before requesting shipping; contact EPIC to arrange shipment.</p>
    <?php endif ?>
    <div class="row g-3">
        <?php foreach ($groupFields as $field): ?>
            <?php [$label, $type, $length, $autocomplete] = \App\Libraries\CustomerDetails::FIELDS[$field]; ?>
            <div class="<?= $field === 'shipping_street' ? 'col-12' : 'col-md-6' ?>">
                <label class="form-label" for="<?= esc($field, 'attr') ?>"><?= esc($label) ?> <span aria-hidden="true">*</span></label>
                <input class="form-control<?= isset($errors[$field]) ? ' is-invalid' : '' ?>" id="<?= esc($field, 'attr') ?>" name="<?= esc($field, 'attr') ?>" type="<?= esc($type, 'attr') ?>" value="<?= esc(old($field, $user[$field] ?? ''), 'attr') ?>" maxlength="<?= (int) $length ?>" autocomplete="<?= esc($autocomplete, 'attr') ?>" required>
                <?php if (isset($errors[$field])): ?><div class="invalid-feedback"><?= esc($errors[$field]) ?></div><?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
</fieldset>
<?php endforeach ?>
