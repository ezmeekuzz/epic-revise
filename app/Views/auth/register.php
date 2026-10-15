<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto auth-card-wide">
                <div class="text-center mb-4">
                    <img class="auth-logo" src="<?= base_url('assets/images/epic-logo.png') ?>" alt="Epic Storage Solutions">
                    <h1 class="h3 mt-3 mb-2">Create your customer account</h1>
                    <p class="text-muted-brand mb-0">Keep your Epic Storage details together in one secure place.</p>
                </div>

                <?php $errors = session()->getFlashdata('errors') ?? []; ?>
                <form action="<?= site_url('register') ?>" method="post" data-loading-title="Creating your account" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Student name</label>
                        <input type="text" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= esc(old('name'), 'attr') ?>" autocomplete="name" minlength="2" maxlength="100" required autofocus>
                        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= esc($errors['name']) ?></div><?php endif ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email'), 'attr') ?>" autocomplete="email" maxlength="254" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?>
                    </div>
                    <?= view('partials/customer_details', ['errors' => $errors, 'user' => []]) ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="passwordHelp" required>
                            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= esc($errors['password']) ?></div><?php endif ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">Confirm password</label>
                            <input type="password" class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="12" maxlength="72" required>
                            <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= esc($errors['password_confirm']) ?></div><?php endif ?>
                        </div>
                    </div>
                    <p id="passwordHelp" class="form-text mb-3">Use at least 12 characters. A long, unique passphrase works best.</p>
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input<?= isset($errors['terms']) ? ' is-invalid' : '' ?>" id="terms" name="terms" value="1"<?= old('terms') === '1' ? ' checked' : '' ?> required>
                        <label class="form-check-label" for="terms">I agree to the <a href="<?= site_url('terms') ?>" target="_blank" rel="noopener">Terms and Conditions</a>.</label>
                        <?php if (isset($errors['terms'])): ?><div class="invalid-feedback"><?= esc($errors['terms']) ?></div><?php endif ?>
                    </div>
                    <button type="submit" class="btn btn-brand-primary w-100">Create account</button>
                    <p class="text-center text-muted-brand mt-4 mb-0 small">Already registered? <a href="<?= site_url('login') ?>">Log in</a></p>
                </form>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
