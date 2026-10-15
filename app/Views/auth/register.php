<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto auth-card-wide">
                <div class="text-center mb-4">
                    <img class="auth-logo" src="<?= base_url('assets/images/epic-logo.png') ?>" alt="EPIC Storage Solutions">
                    <h1 class="h3 mt-3 mb-2">Create your customer account</h1>
                    <p class="text-muted-brand mb-0">Keep your EPIC Storage details together in one secure place.</p>
                </div>

                <?php $errors = session()->getFlashdata('errors') ?? []; ?>
                <form action="<?= site_url('register') ?>" method="post" data-registration-form data-loading-title="Creating your account" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="name">Student name</label>
                        <input type="text" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name" value="<?= esc(old('name'), 'attr') ?>" autocomplete="name" minlength="2" maxlength="100" required autofocus>
                        <div id="nameError" class="invalid-feedback"><?= esc($errors['name'] ?? 'Please enter the student name.') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email'), 'attr') ?>" autocomplete="email" maxlength="254" required>
                        <div id="emailError" class="invalid-feedback"><?= esc($errors['email'] ?? 'Please enter a valid email address.') ?></div>
                    </div>
                    <?= view('partials/customer_details', ['errors' => $errors, 'user' => [], 'includeShipping' => false]) ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="passwordHelp" required>
                            <div id="passwordError" class="invalid-feedback"><?= esc($errors['password'] ?? 'Use a password with 12 to 72 characters.') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password_confirm">Confirm password</label>
                            <input type="password" class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="12" maxlength="72" required>
                            <div id="password_confirmError" class="invalid-feedback"><?= esc($errors['password_confirm'] ?? 'Enter the same password again.') ?></div>
                        </div>
                    </div>
                    <p id="passwordHelp" class="form-text mb-3">Use at least 12 characters. A long, unique passphrase works best.</p>
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input<?= isset($errors['terms']) ? ' is-invalid' : '' ?>" id="terms" name="terms" value="1"<?= old('terms') === '1' ? ' checked' : '' ?> required>
                        <label class="form-check-label" for="terms">I agree to the <a href="<?= site_url('terms') ?>" target="_blank" rel="noopener">Terms and Conditions</a>.</label>
                        <div id="termsError" class="invalid-feedback"><?= esc($errors['terms'] ?? 'Please agree to the Terms and Conditions.') ?></div>
                    </div>
                    <button type="submit" class="btn btn-brand-primary w-100">Create account</button>
                    <p class="text-center text-muted-brand mt-4 mb-0 small">Already registered? <a href="<?= site_url('login') ?>">Log in</a></p>
                </form>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/registration.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/registration.js') ?>"></script>
<?= $this->endSection() ?>
