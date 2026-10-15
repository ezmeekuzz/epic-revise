<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner has-bg" style="background-image:url('<?= base_url('assets/images/6-1.png') ?>');">
    <div class="container banner-inner">
      <div class="row g-5">
        <div class="col-lg-5">
          <span class="eyebrow">Get In Touch</span>
          <h1 class="mt-3">Questions? Comments? You're in the right place.</h1>
          <p class="hero-lede mt-3">Rather talk to us in person? Give us a call at <strong>336.549.5216</strong>.</p>
          <div class="footer2-socials mt-3">
            <a href="https://www.linkedin.com/company/epic-storage-solutions/" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7 10v7M7 7v.01M11 17v-4.5a2 2 0 0 1 4 0V17"/></svg></a>
            <a href="https://www.facebook.com/epicstoragesolutions/" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M15 8h2V5h-2a4 4 0 0 0-4 4v2H9v3h2v6h3v-6h2.2l.8-3H14V9c0-.6.4-1 1-1z"/></svg></a>
            <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1"/></svg></a>
            <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M22 5.9c-.7.3-1.5.6-2.3.7.8-.5 1.4-1.3 1.7-2.3-.8.5-1.6.8-2.6 1a4 4 0 0 0-6.9 3.7A11.5 11.5 0 0 1 3.6 4.9a4.1 4.1 0 0 0 1.2 5.5c-.6 0-1.2-.2-1.7-.5v.1a4.1 4.1 0 0 0 3.3 4 4 4 0 0 1-1.8.1 4.1 4.1 0 0 0 3.8 2.9A8.2 8.2 0 0 1 2 18.6a11.6 11.6 0 0 0 6.3 1.8c7.5 0 11.7-6.4 11.7-11.9v-.5c.8-.6 1.5-1.3 2-2.1z"/></svg></a>
          </div>

          <div class="contact-info-grid">
            <a class="contact-info-card" href="tel:3365495216">
              <span class="contact-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.4c.9.4 1.9.6 2.9.7a2 2 0 0 1 1.7 2.1z"/></svg></span>
              <span><strong>336.549.5216</strong><small>High-touch support, Monday&ndash;Friday</small></span>
            </a>
            <a class="contact-info-card" href="mailto:support@epicstoragehpu.com">
              <span class="contact-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg></span>
              <span><strong>support@epicstoragehpu.com</strong><small>Sales &amp; support inquiries</small></span>
            </a>
            <div class="contact-info-card">
              <span class="contact-info-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <span><strong>High Point, NC</strong><small>Locally owned &amp; operated since 2009</small></span>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="contact-card">
            <form id="sendmessage" action="<?= site_url('contact/sendMessage') ?>" method="POST" novalidate>
              <?= csrf_field() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="firstname">First Name</label>
                  <input type="text" class="form-control" name="firstname" id="firstname" required>
                  <div class="invalid-feedback">Please enter your first name.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="lastname">Last Name</label>
                  <input type="text" class="form-control" name="lastname" id="lastname" required>
                  <div class="invalid-feedback">Please enter your last name.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="phone">Phone</label>
                  <input type="tel" class="form-control" name="phone" id="phone" required>
                  <div class="invalid-feedback">Please enter a phone number.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="email">Email</label>
                  <input type="email" class="form-control" name="email" id="email" required>
                  <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="col-12">
                  <label class="form-label" for="message">Message</label>
                  <textarea class="form-control" name="message" id="message" rows="5" required></textarea>
                  <div class="invalid-feedback">Please enter a message.</div>
                </div>
                <div class="col-12">
                  <button type="submit" id="contactSubmit" class="btn btn-brand-primary w-100">Submit Now</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>
<?= $this->endSection() ?>
