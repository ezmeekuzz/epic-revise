<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
    <section class="auth-shell py-5 py-lg-6">
        <div class="container">
            <div class="auth-card mx-auto auth-card-wide text-center">
                <div class="auth-icon mx-auto mb-4" aria-hidden="true">@</div>
                <p class="eyebrow justify-content-center">One last step</p>
                <h1 class="h3 mt-2 mb-3"><?= esc($heading ?? 'Check your inbox') ?></h1>
                <p class="text-muted-brand mb-4"><?= esc($message ?? 'We sent a confirmation link to your email address. Click it to activate your account and continue your booking.') ?></p>

                <?php if (! empty($email)): ?>
                    <p class="small mb-4"><strong><?= esc($email) ?></strong></p>
                <?php endif ?>

                <?php if (! empty($error)): ?>
                    <div class="alert alert-danger text-start" role="alert"><?= esc($error) ?></div>
                <?php endif ?>

                <?php if (! empty($showResend)): ?>
                    <div class="portal-card text-start mt-4">
                        <h2 class="h6 mb-2">Need another email?</h2>
                        <p class="small text-muted-brand">Enter the address you registered with and we’ll send a fresh confirmation link.</p>
                        <form action="<?= site_url('verify-email/resend') ?>" method="post" class="d-flex flex-column flex-sm-row gap-2" data-loading-title="Sending confirmation email" novalidate>
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="verification_email">Email address</label>
                            <input class="form-control" id="verification_email" name="email" type="email" value="<?= esc($email ?? '', 'attr') ?>" maxlength="254" autocomplete="email" placeholder="you@example.com" required>
                            <button class="btn btn-brand-primary flex-shrink-0" type="submit">Resend link</button>
                        </form>
                    </div>
                <?php endif ?>

                <p class="small text-muted-brand mt-4 mb-0"><a href="<?= site_url('login') ?>">Return to login</a> · <a href="<?= site_url('services') ?>">Back to services</a></p>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
