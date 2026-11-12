<?= $this->extend('layouts/admin_auth') ?>

<?= $this->section('content') ?>
<main class="admin-login-shell">
    <section class="admin-login-panel" aria-labelledby="adminCodeHeading">
        <a class="admin-login-brand" href="<?= site_url('/') ?>" aria-label="Return to EPIC Storage Solutions">
            <img src="<?= base_url('assets/images/epic-logo.png') ?>" alt="EPIC Storage Solutions">
            <span>Administration</span>
        </a>
        <div class="admin-login-heading">
            <p class="eyebrow">Two-factor login</p>
            <h1 id="adminCodeHeading">Enter your code</h1>
            <p>Open your authenticator app and enter the 6-digit code for EPIC Storage Admin.</p>
        </div>
        <form action="<?= site_url('admin/login/verify') ?>" method="post" data-loading-title="Verifying code" novalidate>
            <?= csrf_field() ?>
            <div class="mb-4">
                <label class="form-label" for="admin_code">Authenticator code</label>
                <input class="form-control" id="admin_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required autofocus>
            </div>
            <button class="btn btn-primary admin-login-submit" type="submit">Verify and sign in</button>
        </form>
        <div class="admin-login-footer"><a href="<?= site_url('admin/login') ?>">Start over</a></div>
    </section>
</main>
<?= $this->endSection() ?>
