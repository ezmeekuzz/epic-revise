<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner has-bg" style="background-image:url('<?= base_url('assets/images/intro-banner.png') ?>');">
    <div class="container banner-inner">
      <div class="row">
        <div class="col-lg-7">
          <span class="eyebrow">Our Booking Policy</span>
          <h1 class="mt-3">It's time to schedule your pick‑up</h1>
          <p class="hero-lede mt-3">If you're not ready to schedule now, you'll receive a reminder email from our team. Once you've scheduled a time, you'll receive a confirmation email and a reminder the day prior to your scheduled pickup.</p>
          <p class="hero-lede mt-3">You can change your pickup day or time up to 48 hours prior to your original scheduled time. If you have any questions, please call our office at 336.549.5216.</p>
          <a class="btn btn-brand-primary mt-4" href="<?= site_url('services') ?>">Continue</a>
        </div>
      </div>
    </div>
  </section>

  <?php $bookingOpen = ($bookingSettings['bookings_enabled'] ?? '0') === '1'; ?>
  <section class="py-4" aria-labelledby="bookingAvailabilityTitle">
    <div class="container">
      <div class="portal-card d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
          <span class="status-pill <?= $bookingOpen ? 'status-active' : 'status-inactive' ?>"><?= $bookingOpen ? 'Scheduling available' : 'Scheduling unavailable' ?></span>
          <h2 class="h4 mt-3 mb-2" id="bookingAvailabilityTitle"><?= $bookingOpen ? 'Plan your Epic pickup window' : 'Online scheduling is temporarily paused' ?></h2>
          <?php if ($bookingOpen): ?>
            <p class="text-muted-brand mb-0">Current pickup hours are <?= esc(date('g:i A', strtotime((string) ($bookingSettings['pickup_start_time'] ?? '09:00')))) ?> to <?= esc(date('g:i A', strtotime((string) ($bookingSettings['pickup_end_time'] ?? '17:00')))) ?>. Please plan at least <?= (int) ($bookingSettings['minimum_advance_hours'] ?? 24) ?> hours ahead.</p>
          <?php else: ?>
            <p class="text-muted-brand mb-0"><?= esc($bookingSettings['booking_unavailable_message'] ?? 'Online booking is temporarily unavailable. Please contact our team for assistance.') ?></p>
          <?php endif ?>
        </div>
        <a class="btn <?= $bookingOpen ? 'btn-brand-primary' : 'btn-brand-outline' ?> flex-shrink-0" href="<?= $bookingOpen ? site_url('account') : site_url('contact') ?>"><?= $bookingOpen ? 'Open customer portal' : 'Contact our team' ?></a>
      </div>
    </div>
  </section>

  <section class="section-alt py-5 py-lg-6">
    <div class="container py-2">
      <div class="section-head text-center mx-auto mb-4" style="max-width:640px;">
        <span class="eyebrow justify-content-center">How It Works</span>
        <h2 class="mt-2">Four steps, start to redelivery</h2>
      </div>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 process-rail">
        <div class="col process-step text-center">
          <img src="<?= base_url('assets/images/step-1.png') ?>" alt="" class="step-illustration mx-auto" aria-hidden="true">
          <span class="step-num mx-auto">1</span>
          <h3 class="h6">Sign up &amp; pay</h3>
          <p>Sign up online, customize the services you need, and pay securely with your credit card.</p>
        </div>
        <div class="col process-step text-center">
          <img src="<?= base_url('assets/images/step-2.png') ?>" alt="" class="step-illustration mx-auto" aria-hidden="true">
          <span class="step-num mx-auto">2</span>
          <h3 class="h6">Choose your schedule</h3>
          <p>Select a date and time for us to pick up your EPIC stuff from your dorm room.</p>
        </div>
        <div class="col process-step text-center">
          <img src="<?= base_url('assets/images/step-3.png') ?>" alt="" class="step-illustration mx-auto" aria-hidden="true">
          <span class="step-num mx-auto">3</span>
          <h3 class="h6">Free boxes arrive</h3>
          <p>Complimentary boxes and tape are delivered the week before your scheduled pick‑up.</p>
        </div>
        <div class="col process-step text-center">
          <img src="<?= base_url('assets/images/step-4.png') ?>" alt="" class="step-illustration mx-auto" aria-hidden="true">
          <span class="step-num mx-auto">4</span>
          <h3 class="h6">Pickup, storage &amp; redelivery</h3>
          <p>We store your items securely, then bring everything back when you return.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="rounded-4 p-4 p-lg-5 d-flex flex-wrap gap-4 align-items-center justify-content-between" style="background:var(--navy-deep);color:#fff;">
        <div>
          <h2 class="text-white mb-2">Ready to pick your window?</h2>
          <p class="mb-0" style="color:var(--on-dark-soft);max-width:44ch;">Sign up for a plan first, then choose your pickup and redelivery dates.</p>
        </div>
        <a class="btn btn-brand-secondary" href="<?= site_url('services') ?>">Lets Get Started</a>
      </div>
    </div>
  </section>

</main>
<?= $this->endSection() ?>
