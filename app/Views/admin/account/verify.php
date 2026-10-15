<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="page-heading"><div><p class="eyebrow">Security checkpoint</p><h1>Verify your identity</h1><p>Enter your current administrator password before viewing or changing account credentials.</p></div></div>
<section class="admin-card form-card reauth-card"><div class="card-heading"><div><h2>Administrator verification</h2><p>This verification expires after five minutes and is cleared after a credential change.</p></div></div><form action="<?= site_url('admin/account/verify') ?>" method="post" data-loading-title="Verifying your identity" novalidate><?= csrf_field() ?><div class="mb-4"><label class="form-label" for="password">Current password</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required autofocus></div><div class="form-actions"><a class="btn btn-light" href="<?= site_url('admin') ?>">Cancel</a><button class="btn btn-primary" type="submit">Verify and continue</button></div></form></section>
<?= $this->endSection() ?>
