<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto">
                <?php if (! $tokenValid): ?>
                    <div class="text-center">
                        <div class="auth-icon mx-auto mb-4" aria-hidden="true">!</div>
                        <h1 class="h3 mb-2">This reset link is invalid</h1>
                        <p class="text-muted-brand mb-4">The link is missing, expired, or has already been used.</p>
                        <a class="btn btn-brand-primary w-100" href="<?= site_url('forgot-password') ?>">Request a new link</a>
                        <p class="text-center text-muted-brand mt-4 mb-0 small"><a href="<?= site_url('login') ?>">Return to login</a></p>
                    </div>
                <?php else: ?>
                    <div class="text-center mb-4">
                        <img class="auth-logo" src="<?= base_url('assets/images/epic-logo.png') ?>" alt="EPIC Storage Solutions">
                        <h1 class="h3 mt-3 mb-2">Choose a new password</h1>
                        <p class="text-muted-brand mb-0">Set a new password for your EPIC customer account.</p>
                    </div>

                    <?php $errors = session()->getFlashdata('errors') ?? []; ?>
                    <form action="<?= site_url('reset-password') ?>" method="post" data-loading-title="Updating your password" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="password">New password</label>
                                <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required autofocus>
                                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= esc($errors['password']) ?></div><?php endif ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirm">Confirm password</label>
                                <input type="password" class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="12" maxlength="72" required>
                                <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= esc($errors['password_confirm']) ?></div><?php endif ?>
                            </div>
                        </div>
                        <p class="form-text mb-3">Use at least 12 characters. A long, unique passphrase works best.</p>
                        <button type="submit" class="btn btn-brand-primary w-100">Reset password</button>
                        <p class="text-center text-muted-brand mt-4 mb-0 small"><a href="<?= site_url('login') ?>">Return to login</a></p>
                    </form>
                <?php endif ?>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
