<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner pb-3">
    <div class="container text-center">
      <span class="eyebrow justify-content-center">Our services</span>
      <h1 class="mt-2">Our <span class="accent">EPIC Services</span></h1>
      <p class="hero-lede mx-auto mt-3">Choose your storage package, then create or open your account to order.</p>
    </div>
  </section>

  <section id="serviceSection" class="py-4">
    <div class="container">
      <?= view('partials/storage_packages', ['pricingSettings' => $pricingSettings ?? []]) ?>
    </div>
  </section>

  <section class="section-alt py-5 py-lg-6 mt-5">
    <div class="container py-2">
      <div class="section-head text-center mx-auto mb-4" style="max-width:560px;">
        <span class="eyebrow justify-content-center">Add To Any Plan</span>
        <h2 class="mt-2">Need a little more room?</h2>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <a href="<?= site_url('faqs') ?>#more-boxes" class="text-decoration-none text-reset">
            <div class="category-card">
              <img src="<?= base_url('assets/images/services/1.png') ?>" alt="More boxes icon">
              <h3 class="h6 mb-1">More Boxes</h3>
              <p class="text-muted-brand mb-0">Unlimited</p>
            </div>
          </a>
        </div>
        <div class="col-md-4">
          <a href="#serviceSection" class="text-decoration-none text-reset">
            <div class="category-card">
              <img src="<?= base_url('assets/images/services/2.png') ?>" alt="Additional items icon">
              <h3 class="h6 mb-1">Additional Items</h3>
              <p class="text-muted-brand mb-0">TV, Fridge, Furniture, Cars</p>
            </div>
          </a>
        </div>
        <div class="col-md-4">
          <a href="#serviceSection" class="text-decoration-none text-reset">
            <div class="category-card">
              <img src="<?= base_url('assets/images/services/4.png') ?>" alt="Summer school and study abroad icon">
              <h3 class="h6 mb-1">Summer School &amp; Study Abroad</h3>
              <p class="text-muted-brand mb-0">Endless Options</p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 py-lg-6">
    <div class="container py-2">
      <div class="section-head text-center mx-auto mb-4" style="max-width:560px;">
        <span class="eyebrow justify-content-center">Common Questions</span>
        <h2 class="mt-2">Before you get started</h2>
      </div>
      <div class="accordion accordion-brand mx-auto" style="max-width:760px;" id="servicesFaq">
        <div class="accordion-item">
          <h3 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#sf1">What is part of the basic package that you provide?</button></h3>
          <div id="sf1" class="accordion-collapse collapse show" data-bs-parent="#servicesFaq"><div class="accordion-body">The typical student uses 5 large EPIC boxes (18"×18"×24"). Included: complimentary tape, pick‑up from your dorm room, secure storage, and delivery back to your new dorm room. You can add boxes or extra items right here.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sf2">When should I sign up?</button></h3>
          <div id="sf2" class="accordion-collapse collapse" data-bs-parent="#servicesFaq"><div class="accordion-body">Sign up now with our base package to reserve your spot. You can add additional items and update your pickup schedule later via your order confirmation email.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sf3">Do I need to be present when you pick up or drop off?</button></h3>
          <div id="sf3" class="accordion-collapse collapse" data-bs-parent="#servicesFaq"><div class="accordion-body">Whatever is convenient for you — as a certified partner of HPU, we have access to the rooms.</div></div>
        </div>
        <div class="accordion-item">
          <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sf4">Do you provide packing services?</button></h3>
          <div id="sf4" class="accordion-collapse collapse" data-bs-parent="#servicesFaq"><div class="accordion-body">Yes — the cost is $150 an hour per staff member. Call our office at 336.549.5216 for details and scheduling.</div></div>
        </div>
        <div class="accordion-item" id="more-boxes">
          <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sf5">What if we need additional boxes or need to store non‑boxed items?</button></h3>
          <div id="sf5" class="accordion-collapse collapse" data-bs-parent="#servicesFaq"><div class="accordion-body">Call our office at 336.549.5216 and we'll arrange to have extra boxes and supplies delivered to your room. For non‑boxed items we'll supply labels for you upon pick‑up.</div></div>
        </div>
      </div>
      <div class="text-center mt-4">
        <a class="btn btn-brand-outline" href="<?= site_url('faqs') ?>">I have more questions</a>
      </div>
    </div>
  </section>

<?= view('partials/scheduling_info') ?>
</main>
<?= $this->endSection() ?>
