<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner pb-3">
    <div class="container text-center">
      <span class="eyebrow justify-content-center">Our Clients Say</span>
      <h1 class="mt-2">EPIC <span class="accent">Testimonials</span></h1>
    </div>
  </section>

  <section class="py-4">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6 order-lg-2 text-center">
          <img src="<?= base_url('assets/images/model-girl.png') ?>" alt="HPU student with packed boxes" class="img-fluid">
        </div>
        <div class="col-lg-6">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="testimonial-card">
                <img src="<?= base_url('assets/images/quote.png') ?>" class="quote-mark" alt="" aria-hidden="true">
                <p class="mb-0">When I called Epic Storage, Anissa was friendly, professional, and above all helpful! They took care of everything for a very reasonable cost. They supply the boxes and tape on campus, and all we needed to do was pack up. They picked everything up and took it to storage. On the day we came back, they delivered right to the dorm room. We are excited to use them again this year!</p>
                <div class="testimonial-foot"><strong class="d-block">Steve</strong><span class="text-muted-brand">Parent from Florida</span></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="testimonial-card">
                <img src="<?= base_url('assets/images/quote.png') ?>" class="quote-mark" alt="" aria-hidden="true">
                <p class="mb-0">Working with Anissa and the EPIC team was an excellent experience from start to finish. Knowing that my kids' stuff was picked up on time and literally waiting in their rooms when we arrived back after summer was worth its weight in gold. Dealing with Anissa couldn't be easier — she is very responsive, friendly, and knows every nook and cranny of that campus.</p>
                <div class="testimonial-foot"><strong class="d-block">Jen</strong><span class="text-muted-brand">Parent from New Jersey</span></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="testimonial-card">
                <img src="<?= base_url('assets/images/quote.png') ?>" class="quote-mark" alt="" aria-hidden="true">
                <p class="mb-0">EPIC truly made summer storage for our daughter the best experience possible. Anissa and her friendly staff helped us every step of the way. We felt like VIP customers.</p>
                <div class="testimonial-foot"><strong class="d-block">Kerri</strong><span class="text-muted-brand">Connecticut</span></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="testimonial-card">
                <img src="<?= base_url('assets/images/quote.png') ?>" class="quote-mark" alt="" aria-hidden="true">
                <p class="mb-0">EPIC gives every student the &ldquo;White Glove&rdquo; treatment and continued our extraordinary experience that HPU prides itself on. They are an asset to HPU families and the University.</p>
                <div class="testimonial-foot"><strong class="d-block">Jim</strong><span class="text-muted-brand">Long Island, NY</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row justify-content-center mt-4">
        <div class="col-lg-8">
          <div class="testimonial-card">
            <img src="<?= base_url('assets/images/quote.png') ?>" class="quote-mark" alt="" aria-hidden="true">
            <p class="mb-0">We used Epic Storage Solutions to store my daughter's college items. The company is easy to contract, and they provide good guidance concerning your storage needs. We found it very helpful to have them drop off our boxes prior to packing and to have them pick up the packed items for storage. This provided a stress relief to the process, as my daughter was under a time crunch to move out of her dorm.<br><br>Anissa was patient and kind, and she sincerely cared about providing timely and professional service to meet our needs. I would recommend Epic Storage Solutions for any type of storage needs, and I guarantee you that you will receive excellent customer service in the process.</p>
            <div class="testimonial-foot"><strong class="d-block">Carla</strong><span class="text-muted-brand">New Jersey</span></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="rounded-4 p-4 p-lg-5 d-flex flex-wrap gap-4 align-items-center justify-content-between" style="background:var(--navy-deep);color:#fff;">
        <div>
          <h2 class="text-white mb-2">Ready for the &ldquo;White Glove&rdquo; treatment?</h2>
          <p class="mb-0" style="color:var(--on-dark-soft);max-width:44ch;">Join thousands of HPU families who trust EPIC with move‑out.</p>
        </div>
        <a class="btn btn-brand-secondary" href="<?= site_url('services') ?>">Lets Get Started</a>
      </div>
    </div>
  </section>

</main>
<?= $this->endSection() ?>
