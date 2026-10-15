<?= $this->extend('layouts/site') ?>

<?= $this->section('head') ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem", "position": 1, "name": "Home", "item": "https://epicstoragehpu.com/"},
    {"@type": "ListItem", "position": 2, "name": "About", "item": "https://epicstoragehpu.com/about"}
  ]
}
</script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 order-lg-2 text-center">
          <img src="<?= base_url('assets/images/hpu-campus-supplied.webp') ?>" alt="I love High Point University sign on campus" class="img-fluid banner-photo" width="530" height="377" decoding="async">
        </div>
        <div class="col-lg-6">
          <span class="eyebrow">Our Story</span>
          <h1 class="mt-3">About <span class="accent">Us</span></h1>
          <p class="hero-lede mt-3">The mission of EPIC Storage Solutions is to provide High Point University students and parents with a convenient, accountable, and safe moving and storage experience.</p>
          <p class="hero-lede mt-3">Founded by High Point University alum Anisha Roy in 2009, EPIC is locally owned and operated in High Point, NC. After a successful corporate career, Anisha set out to build her own business. Through the years, we've had the opportunity to service thousands of HPU students, helping us evolve into the company we are today.</p>
          <p class="hero-lede mt-3">We pride ourselves in providing a high‑touch and always‑available experience. This approach allows us to directly engage in each customer relationship, placing a personal touch on every parent and student experience.</p>
          <a class="btn btn-brand-primary mt-4" href="<?= site_url('services') ?>">Lets Get Started</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section-alt py-5 py-lg-6">
    <div class="container py-4">
      <div class="section-head text-center mx-auto mb-4" style="max-width:640px;">
        <span class="eyebrow justify-content-center">Why HPU Families Choose EPIC</span>
        <h2 class="mt-2">Locally owned. Locally trusted.</h2>
      </div>
      <div class="fact-band">
        <div class="fact-tile"><span class="fact-value">2009</span><span class="fact-label">Founded &amp; operating in High Point, NC</span></div>
        <div class="fact-tile"><span class="fact-value">Anisha Roy</span><span class="fact-label">Founder — HPU Class of 1991</span></div>
        <div class="fact-tile"><span class="fact-value">1000s</span><span class="fact-label">HPU students served since 2009</span></div>
        <div class="fact-tile"><span class="fact-value">336.549.5216</span><span class="fact-label">Call or text our office directly</span></div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="rounded-4 p-4 p-lg-5 d-flex flex-wrap gap-4 align-items-center justify-content-between" style="background:var(--navy-deep);color:#fff;">
        <div>
          <h2 class="text-white mb-2">Ready to hand off the heavy lifting?</h2>
          <p class="mb-0" style="color:var(--on-dark-soft);max-width:44ch;">Sign up online in minutes and pick your plan.</p>
        </div>
        <a class="btn btn-brand-secondary" href="<?= site_url('services') ?>">Lets Get Started</a>
      </div>
    </div>
  </section>

</main>
<?= $this->endSection() ?>
