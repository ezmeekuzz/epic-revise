<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading"><div><p class="eyebrow">Security</p><h1>Two-factor login</h1><p>Add a one-time code from an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, Authy) as a second step when you sign in.</p></div><a class="btn btn-outline-secondary" href="<?= site_url('admin/account') ?>">Back to account</a></div>
<section class="admin-card form-card">
<?php if ($enabled): ?>
    <div class="card-heading"><div><h2>Two-factor login is on</h2><p>To turn it off, enter a current code from your authenticator app.</p></div></div>
    <form action="<?= site_url('admin/account/two-factor/disable') ?>" method="post" novalidate><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="code">Authenticator code</label><input class="form-control" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required></div>
        <button class="btn btn-outline-danger" type="submit">Turn off two-factor login</button>
    </form>
<?php else: ?>
    <div class="card-heading"><div><h2>Set up your authenticator app</h2><p>In your app choose "Enter a setup key", then enter the key below. Then type the 6-digit code it shows to finish.</p></div></div>
    <dl class="mb-4"><dt>Setup key</dt><dd><code style="font-size:1.1rem;letter-spacing:.08em;word-break:break-all"><?= esc(trim(chunk_split($pending['secret'], 4, ' '))) ?></code></dd><dt>Account</dt><dd><?= esc((string) session()->get('admin_user_email')) ?> &middot; time-based, 6 digits</dd><dt>Setup link (for apps that accept it)</dt><dd><small style="word-break:break-all"><?= esc($pending['uri']) ?></small></dd></dl>
    <form action="<?= site_url('admin/account/two-factor/enable') ?>" method="post" novalidate><?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="code">6-digit code</label><input class="form-control" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required></div>
        <button class="btn btn-primary" type="submit">Turn on two-factor login</button>
    </form>
<?php endif ?>
</section>
<?= $this->endSection() ?>
