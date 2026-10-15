<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div><p class="eyebrow">Settings</p><h1>Admin account</h1><p>Keep your administrator identity and sign-in credentials current.</p></div>
</div>

<div class="settings-grid">
    <section class="admin-card form-card">
        <div class="card-heading"><div><h2>Profile details</h2><p>Used in the admin navigation and for sign-in.</p></div></div>
        <?php $profileErrors = session()->getFlashdata('profileErrors') ?? []; ?>
        <form action="<?= site_url('admin/account/profile') ?>" method="post" data-loading-title="Updating your profile" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control<?= isset($profileErrors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" type="text" value="<?= esc(old('name', $user['name']), 'attr') ?>" minlength="2" maxlength="100" required>
                <?php if (isset($profileErrors['name'])): ?><div class="invalid-feedback"><?= esc($profileErrors['name']) ?></div><?php endif ?>
            </div>
            <div class="mb-4">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control<?= isset($profileErrors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= esc(old('email', $user['email']), 'attr') ?>" maxlength="254" required>
                <?php if (isset($profileErrors['email'])): ?><div class="invalid-feedback"><?= esc($profileErrors['email']) ?></div><?php endif ?>
            </div>
            <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
    </section>

    <section class="admin-card form-card">
        <div class="card-heading"><div><h2>Change password</h2><p>Confirm your current password before choosing a new one.</p></div></div>
        <?php $passwordErrors = session()->getFlashdata('passwordErrors') ?? []; ?>
        <form action="<?= site_url('admin/account/password') ?>" method="post" data-loading-title="Changing your password" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input class="form-control<?= isset($passwordErrors['current_password']) ? ' is-invalid' : '' ?>" id="current_password" name="current_password" type="password" maxlength="72" autocomplete="current-password" required>
                <?php if (isset($passwordErrors['current_password'])): ?><div class="invalid-feedback"><?= esc($passwordErrors['current_password']) ?></div><?php endif ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">New password</label>
                <input class="form-control<?= isset($passwordErrors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                <?php if (isset($passwordErrors['password'])): ?><div class="invalid-feedback"><?= esc($passwordErrors['password']) ?></div><?php endif ?>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirm">Confirm new password</label>
                <input class="form-control<?= isset($passwordErrors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                <?php if (isset($passwordErrors['password_confirm'])): ?><div class="invalid-feedback"><?= esc($passwordErrors['password_confirm']) ?></div><?php endif ?>
            </div>
            <button class="btn btn-primary" type="submit">Change password</button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
