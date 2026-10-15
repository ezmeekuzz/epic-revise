<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 order-lg-2 text-center">
          <img src="<?= base_url('assets/images/ship.png') ?>" alt="EPIC Storage shipping box" class="img-fluid banner-photo">
        </div>
        <div class="col-lg-6">
          <span class="eyebrow">Not Coming Back To Campus?</span>
          <h1 class="mt-3">Shipping</h1>
          <p class="hero-lede mt-3">Shipping services are offered in partnership with United Parcel Service (UPS). If you need to have items mailed, we can accommodate your request.</p>
          <p class="hero-lede mt-3">EPIC will supply you with a large box (18"×18"×24") and shipping tape. Each box will have a $20 handling fee applied. Please call our office at 336.549.5216 and we can help coordinate and estimate the UPS shipping cost.</p>
          <a class="btn btn-brand-primary mt-4" href="<?= site_url('contact') ?>">Lets Get Started</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section-alt py-5 py-lg-6">
    <div class="container py-2">
      <div class="fact-band">
        <div class="fact-tile"><span class="fact-value">18"×18"×24"</span><span class="fact-label">Large box size supplied</span></div>
        <div class="fact-tile"><span class="fact-value">$20</span><span class="fact-label">Handling fee per box</span></div>
        <div class="fact-tile"><span class="fact-value">UPS</span><span class="fact-label">Shipping partner</span></div>
        <div class="fact-tile"><span class="fact-value">336.549.5216</span><span class="fact-label">Call to coordinate &amp; estimate cost</span></div>
      </div>
    </div>
  </section>

</main>
<?= $this->endSection() ?>
