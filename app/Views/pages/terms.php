<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>
  <section class="page-banner pb-3">
    <div class="container">
      <span class="eyebrow">Legal</span>
      <h1 class="mt-2">Terms &amp; Conditions</h1>
    </div>
  </section>

  <section class="py-4 pb-5">
    <div class="container">
      <div class="legal-content mx-auto">
        <?php if ($currentTerms !== ''): ?>
            <?= view('partials/current_terms', ['currentTerms' => $currentTerms]) ?>
        <?php else: ?>
            <?= view('partials/legacy_terms') ?>
        <?php endif ?>
        <details class="mt-5 legal-archive"><summary>Previous terms &mdash; expired on September 1st, 2016</summary>
            <p class="mt-3">Historical terms retained for reference to previous orders.</p>
            <?= view('partials/legacy_terms') ?>
        </details>
      </div>
    </div>
  </section>
</main>
<?= $this->endSection() ?>
