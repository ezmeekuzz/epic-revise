<?= $this->extend('layouts/account') ?>

<?= $this->section('content') ?>
<?php
$errors = session()->getFlashdata('errors') ?? [];
$selectedDormId = (string) old('dorm_id', (string) ($user['dorm_id'] ?? ''));
?>
<main class="account-main">
    <section class="py-5 py-lg-6">
        <div class="container">
            <div class="account-hero mb-4 d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div>
                    <span class="eyebrow">Customer profile</span>
                    <h1 class="h2 mt-2 mb-2">Personal details</h1>
                    <p class="mb-0 text-muted-brand">Keep your contact, address, parent or guardian, and default dorm information current.</p>
                </div>
                <a class="btn btn-brand-outline" href="<?= site_url('account') ?>">Back to account</a>
            </div>

            <form action="<?= site_url('account/profile') ?>" method="post" data-loading-title="Saving your personal details" novalidate>
                <?= csrf_field() ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="portal-card mb-4">
                            <h2 class="h4">Contact information</h2>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label" for="name">Full name</label><input class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= esc(old('name', $user['name']), 'attr') ?>" maxlength="100" autocomplete="name" required><?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= esc($errors['name']) ?></div><?php endif ?></div>
                                <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= esc(old('email', $user['email']), 'attr') ?>" maxlength="254" autocomplete="email" required><?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?></div>
                            </div>
                            <?= view('partials/customer_details', ['errors' => $errors, 'user' => $user]) ?>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="portal-card booking-price-summary">
                            <h2 class="h4">Default pickup dorm</h2>
                            <p class="text-muted-brand small">Optional. This dorm and room will be preselected on new service requests. You can change them for each booking later.</p>
                            <div class="mb-3"><label class="form-label" for="dorm_id">Dorm / pickup location</label><select class="form-select<?= isset($errors['dorm_id']) ? ' is-invalid' : '' ?>" id="dorm_id" name="dorm_id"><option value="">No default dorm</option><?php foreach ($dorms as $dorm): ?><option value="<?= (int) $dorm['id'] ?>"<?= $selectedDormId === (string) $dorm['id'] ? ' selected' : '' ?>><?= esc($dorm['name']) ?></option><?php endforeach ?></select><?php if (isset($errors['dorm_id'])): ?><div class="invalid-feedback"><?= esc($errors['dorm_id']) ?></div><?php endif ?></div>
                            <div class="mb-4"><label class="form-label" for="dorm_room_number">Dorm room <span class="text-muted-brand">(optional)</span></label><input class="form-control<?= isset($errors['dorm_room_number']) ? ' is-invalid' : '' ?>" id="dorm_room_number" name="dorm_room_number" value="<?= esc(old('dorm_room_number', $user['dorm_room_number'] ?? ''), 'attr') ?>" maxlength="30"><?php if (isset($errors['dorm_room_number'])): ?><div class="invalid-feedback"><?= esc($errors['dorm_room_number']) ?></div><?php endif ?></div>
                            <div class="d-grid gap-2"><button class="btn btn-brand-primary" type="submit">Save personal details</button><a class="btn btn-light" href="<?= site_url('account') ?>">Cancel</a></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
