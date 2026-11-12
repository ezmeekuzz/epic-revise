<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$editing = $user !== null;
$errors  = session()->getFlashdata('errors') ?? [];
?>
<div class="page-heading">
    <div><p class="eyebrow">Customer management</p><h1><?= $editing ? 'Edit customer' : 'Add customer' ?></h1><p><?= $editing ? 'Update customer status or account details.' : 'Create a customer account. Create a customer or administrator account is no longer combined; administrator credentials are managed separately.' ?></p></div>
    <a class="btn btn-outline-secondary" href="<?= site_url('admin/users') ?>">Back to users</a>
</div>

<section class="admin-card form-card">
    <form action="<?= $editing ? site_url('admin/users/' . $user['id']) : site_url('admin/users') ?>" method="post" data-loading-title="<?= $editing ? 'Saving user changes' : 'Creating user account' ?>" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" type="text" value="<?= esc(old('name', $user['name'] ?? ''), 'attr') ?>" minlength="2" maxlength="100" required autofocus>
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= esc($errors['name']) ?></div><?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= esc(old('email', $user['email'] ?? ''), 'attr') ?>" maxlength="254" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="status">Status</label>
                <?php $selectedStatus = old('status', $user['status'] ?? 'active'); ?>
                <select class="form-select<?= isset($errors['status']) ? ' is-invalid' : '' ?>" id="status" name="status" required><option value="active"<?= $selectedStatus === 'active' ? ' selected' : '' ?>>Active</option><option value="pending"<?= $selectedStatus === 'pending' ? ' selected' : '' ?>>Pending email confirmation</option><option value="inactive"<?= $selectedStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option></select>
                <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= esc($errors['status']) ?></div><?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password">Password (optional)</label>
                <input class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password">
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= esc($errors['password']) ?></div><?php endif ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="password_confirm">Confirm password</label>
                <input class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="72" autocomplete="new-password">
                <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= esc($errors['password_confirm']) ?></div><?php endif ?>
            </div>
        </div>
        <?= view('partials/customer_details', ['errors' => $errors, 'user' => $user ?? []]) ?>
        <p class="form-hint"><?= $editing ? 'Leave password fields blank to keep the current password.' : 'Customers created here are active right away, so no email confirmation is needed. Leave the password blank to let the customer set one later with Forgot password, or enter one of at least 12 characters.' ?></p>
        <div class="form-actions"><a class="btn btn-light" href="<?= site_url('admin/users') ?>">Cancel</a><?php if ($editing): ?><a class="btn btn-outline-primary" href="<?= site_url('admin/bookings/new?user=' . (int) $user['id']) ?>">Create order</a><button class="btn btn-primary" type="submit">Save changes</button><?php else: ?><button class="btn btn-outline-primary" type="submit" name="next" value="list">Create user</button><button class="btn btn-primary" type="submit" name="next" value="order">Create user &amp; start order</button><?php endif ?></div>
    </form>
</section>
<?= $this->endSection() ?>
