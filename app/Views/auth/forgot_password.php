<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto">
                <div class="text-center mb-4">
                    <img class="auth-logo" src="<?= base_url('assets/images/epic-logo.png') ?>" alt="EPIC Storage Solutions">
                    <h1 class="h3 mt-3 mb-2">Forgot your password?</h1>
                    <p class="text-muted-brand mb-0">Enter the email on your EPIC customer account and we will send you a link to reset it.</p>
                </div>

                <?php $errors = session()->getFlashdata('errors') ?? []; ?>
                <form action="<?= site_url('forgot-password') ?>" method="post" data-loading-title="Sending reset link" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label class="form-label" for="email">Email address</label>
                        <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= esc(old('email'), 'attr') ?>" autocomplete="email" maxlength="254" required autofocus>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= esc($errors['email']) ?></div><?php endif ?>
                    </div>
                    <button type="submit" class="btn btn-brand-primary w-100">Send reset link</button>
                    <p class="text-center text-muted-brand mt-4 mb-0 small"><a href="<?= site_url('login') ?>">Return to login</a></p>
                </form>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
