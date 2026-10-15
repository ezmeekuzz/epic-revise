<?= $this->extend('layouts/admin_auth') ?>

<?= $this->section('content') ?>
<main class="admin-login-shell">
    <section class="admin-login-panel" aria-labelledby="adminLoginHeading">
        <a class="admin-login-brand" href="<?= site_url('/') ?>" aria-label="Return to Epic Storage Solutions">
            <img src="<?= base_url('assets/images/epic-logo.png') ?>" alt="Epic Storage Solutions">
            <span>Administration</span>
        </a>

        <div class="admin-login-heading">
            <p class="eyebrow">Authorized personnel only</p>
            <h1 id="adminLoginHeading">Admin sign in</h1>
            <p>Use your administrator credentials to access operations, bookings, and settings.</p>
        </div>

        <?php $errors = session()->getFlashdata('errors') ?? []; ?>
        <form action="<?= site_url('admin/login') ?>" method="post" data-loading-title="Signing in to Epic Admin" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="admin_email">Administrator email</label>
                <input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="admin_email" name="email" type="email" value="<?= esc(old('email'), 'attr') ?>" autocomplete="username" maxlength="254" required autofocus>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?>
            </div>
            <div class="mb-4">
                <label class="form-label" for="admin_password">Password</label>
                <div class="input-group has-validation">
                    <input class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="admin_password" name="password" type="password" autocomplete="current-password" maxlength="72" required>
                    <button class="btn btn-outline-secondary password-toggle" type="button" data-password-toggle="admin_password" aria-label="Show password">Show</button>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= esc($errors['password']) ?></div><?php endif ?>
                </div>
            </div>
            <button class="btn btn-primary admin-login-submit" type="submit">Sign in to admin</button>
        </form>

        <div class="admin-login-footer">
            <a href="<?= site_url('/') ?>">Return to website</a>
            <span aria-hidden="true">&middot;</span>
            <a href="<?= site_url('login') ?>">Customer login</a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
