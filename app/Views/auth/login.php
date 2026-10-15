<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto">
                <div class="text-center mb-4">
                    <img class="auth-logo" src="<?= base_url('assets/images/epic-logo.png') ?>" alt="Epic Storage Solutions">
                    <h1 class="h3 mt-3 mb-2">Welcome back</h1>
                    <p class="text-muted-brand mb-0">Log in to manage your Epic customer account.</p>
                </div>

                <?php $errors = session()->getFlashdata('errors') ?? []; ?>
                <form action="<?= site_url('login') ?>" method="post" data-loading-title="Signing you in" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email'), 'attr') ?>" autocomplete="email" maxlength="254" required autofocus>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label mb-0" for="password">Password</label>
                            <a class="small" href="<?= site_url('forgot-password') ?>">Forgot password?</a>
                        </div>
                        <div class="input-group has-validation">
                            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" autocomplete="current-password" maxlength="72" required>
                            <button class="btn btn-brand-outline password-toggle" type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= esc($errors['password']) ?></div><?php endif ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand-primary w-100">Log in securely</button>
                    <p class="text-center text-muted-brand mt-4 mb-0 small">New to Epic? <a href="<?= site_url('register') ?>">Create a customer account</a></p>
                </form>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
