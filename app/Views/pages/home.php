<?= $this->extend('layouts/site') ?>

<?= $this->section('head') ?>
<link rel="preload" as="image" href="<?= base_url('assets/images/Two_women_high_five_2K_202609071746.png') ?>" fetchpriority="high">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://epicstoragehpu.com/#organization",
      "name": "EPIC Storage Solutions",
      "url": "https://epicstoragehpu.com/",
      "logo": {
        "@type": "ImageObject",
        "url": "https://epicstoragehpu.com/assets/images/epic-logo.png",
        "width": 220,
        "height": 107
      },
      "foundingDate": "2009",
      "founder": {
        "@type": "Person",
        "name": "Anisha Roy"
      },
      "areaServed": {
        "@type": "Place",
        "name": "High Point, North Carolina"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "telephone": "+1-336-549-5216",
        "email": "support@epicstoragehpu.com",
        "contactType": "customer support",
        "areaServed": "US",
        "availableLanguage": "English"
      },
      "sameAs": [
        "https://www.facebook.com/epicstoragesolutions/",
        "https://www.linkedin.com/company/epic-storage-solutions/"
      ]
    },
    {
      "@type": "WebSite",
      "@id": "https://epicstoragehpu.com/#website",
      "url": "https://epicstoragehpu.com/",
      "name": "EPIC Storage Solutions",
      "publisher": {
        "@id": "https://epicstoragehpu.com/#organization"
      },
      "inLanguage": "en-US"
    },
    {
      "@type": "WebPage",
      "@id": "https://epicstoragehpu.com/#webpage",
      "url": "https://epicstoragehpu.com/",
      "name": "HPU Dorm Storage & Moving | EPIC Storage Solutions",
      "description": "Dorm-to-dorm summer storage for High Point University students, including boxes, pickup, secure storage, and redelivery in High Point, NC.",
      "isPartOf": {
        "@id": "https://epicstoragehpu.com/#website"
      },
      "about": {
        "@id": "https://epicstoragehpu.com/#organization"
      },
      "inLanguage": "en-US"
    }
  ]
}
</script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $summerStoragePrice = number_format((float) ($pricingSettings['summer_storage_base_price'] ?? 450), 2); $summerAdvantageBoxPrice = number_format((float) ($pricingSettings['summer_advantage_box_price'] ?? 50), 2); ?>
<main>

  <section class="hero2">
    <div class="hero2-media" aria-hidden="true"></div>
    <div class="hero2-scrim" aria-hidden="true"></div>

    <div class="container hero2-content">
      <div class="row">
        <div class="col-lg-7">
          <h1>Dorm to Dorm<br><span class="accent">Storage Solutions</span></h1>
          <p class="lede">EPIC Storage Solutions gives High Point University students and parents a convenient, accountable, and safe way to store and move between semesters.</p>

          <ul class="hero2-checklist">
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Boxes and packing tape</li>
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Secured storage</li>
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Pick-up from your dorm room</li>
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Redeliver back to your dorm room</li>
          </ul>

          <p class="hero2-reassure">We take the burden of moving each semester off your mind, so you can focus on the reason you're at High Point — your education.</p>

          <div class="hero2-actions">
            <a class="btn btn-brand-primary" href="<?= site_url('services') ?>">Let's Get Started</a>
            <a class="btn btn-on-dark" href="tel:3365495216">Call&nbsp;336.549.5216</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="stats2">
    <div class="container">
      <div class="stats2-grid">
        <div class="stats2-item">
          <div class="stats2-value">2009</div>
          <div class="stats2-label">Founded &amp; locally owned in High Point, NC</div>
        </div>
        <div class="stats2-item">
          <div class="stats2-value">1,000s</div>
          <div class="stats2-label">Thousands of High Point students served</div>
        </div>
        <div class="stats2-item">
          <div class="stats2-value">HPU '91</div>
          <div class="stats2-label">Founded by High Point University alum Anisha Roy</div>
        </div>
      </div>
    </div>
  </section>

  <section class="banner2">
    <div class="container">
      <div class="banner2-card">
        <div class="banner2-panel">
          <h2><?= esc($generalSettings['homepage_message_title'] ?? 'Welcome back to HPU') ?></h2>
          <p><?= esc($generalSettings['homepage_message_body'] ?? '') ?></p>
        </div>
        <div class="banner2-photo" role="img" aria-label="EPIC branded storage boxes"></div>
      </div>
    </div>
  </section>

  <section id="services" class="services2">
    <div class="container">
      <div class="services2-head">
        <span class="pill-eyebrow">Our EPIC Services</span>
        <h2>Two ways to store your stuff</h2>
        <p>Pick the package that matches where you are in your HPU journey — we'll handle the rest.</p>
      </div>

      <div class="split-card" data-plan-card>
        <div class="split-photo img-storage" role="img" aria-label="Real EPIC Storage Solutions boxes with students' dorm belongings"></div>
        <div class="split-panel">
          <span class="pill-badge-outline">April – August</span>
          <h3>Summer Storage</h3>
          <p class="plan-audience2">For current HPU students. Standard package includes:</p>
          <div class="split-price-row">
            <span class="split-price">$<?= esc($summerStoragePrice) ?></span>
            <span class="split-price-note">flat rate</span>
          </div>
          <ul class="split-bullets"><li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>5 large boxes (18&quot; &times; 18&quot; &times; 24&quot;)</li><li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Packing tape and supplies</li><li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Secured storage</li><li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Pickup from your dorm</li><li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Re-delivery to your dorm</li></ul>
          <p class="split-note"><strong>Good to know:</strong> additional boxes and items can be added. Shipping services are available, along with summer school and study abroad options.</p>

          <a class="btn btn-brand-secondary" href="<?= site_url('account/bookings/new?booking_type=summer_storage') ?>">Let's Get Started</a>
        </div>
      </div>

      <div class="split-card reverse" data-plan-card>
        <div class="split-photo img-advantage" role="img" aria-label="Labeling a box for the Summer Advantage program"></div>
        <div class="split-panel">
          <span class="pill-badge-outline">July – August</span>
          <h3>Summer Advantage</h3>
          <p class="plan-audience2">For incoming HPU freshmen attending the Summer Advantage Program.</p>
          <div class="split-price-row">
            <span class="split-price">$<?= esc($summerAdvantageBoxPrice) ?></span>
            <span class="split-price-note">per box</span>
          </div>
          <p class="plan-audience2 plan-detail2">Large box (18"×18"×24") — ordered per box</p>
          <ul class="split-bullets">
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Packing tape and supplies</li>
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Secured storage</li>
            <li><span class="check-circle"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>Pick-up and redelivery to your dorm</li>
          </ul>
          <p class="split-note"><strong>Good to know:</strong> additional items can be added, and shipping services are available.</p>

          <a class="btn btn-brand-secondary" href="<?= site_url('account/bookings/new?booking_type=summer_advantage') ?>">Let's Get Started</a>
        </div>
      </div>
    </div>
  </section>

  <section id="how-it-works" class="process2">
    <div class="container">
      <div class="process2-head">
        <div>
          <span class="pill-eyebrow solid">The Process</span>
          <h2>How it works</h2>
        </div>
        <p>Four simple steps between drop-off and move-in day.</p>
      </div>

      <div class="process2-grid">
        <div class="process2-card fill">
          <span class="process2-icon"><img src="<?= base_url('assets/images/noun_SignUp_8178978.png') ?>" alt=""></span>
          <span class="process2-num">1</span>
          <h3>Sign up</h3>
          <p>Sign up online, customize the services you need, and pay securely by credit card on the EPIC site.</p>
        </div>
        <div class="process2-card outline">
          <span class="process2-icon"><img src="<?= base_url('assets/images/noun_schedule_8469263.png') ?>" alt=""></span>
          <span class="process2-num">2</span>
          <h3>Schedule</h3>
          <p>Go to our easy-to-use scheduling page to select a date and time for us to pick up your stuff from your dorm room.</p>
        </div>
        <div class="process2-card fill">
          <span class="process2-icon"><img src="<?= base_url('assets/images/noun_pack_8473700.png') ?>" alt=""></span>
          <span class="process2-num">3</span>
          <h3>Pack</h3>
          <p>Complimentary boxes and packing tape are available before your pick-up date, with all the specifics sent by email.</p>
        </div>
        <div class="process2-card outline">
          <span class="process2-icon"><img src="<?= base_url('assets/images/noun_ProductReturn_5063282.png') ?>" alt=""></span>
          <span class="process2-num">4</span>
          <h3>Store &amp; return</h3>
          <p>EPIC picks up your items, stores them securely while you're away, and brings everything back when it's time.</p>
        </div>
      </div>

      <div class="process2-actions">
        <a class="btn btn-brand-secondary" href="<?= site_url('services') ?>">Let's Get Started</a>
        <a class="btn btn-on-dark" href="tel:3365495216">Call&nbsp;336.549.5216</a>
      </div>
    </div>
  </section>

  <section id="about" class="about2">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-5">
          <div class="about2-photo-wrap">
            <div class="about2-photo" role="img" aria-label="I love High Point University sign on campus"></div>
            <div class="about2-scrim" aria-hidden="true"></div>
            <div class="about2-caption">
              <strong>High Point University</strong>
              <span>Serving HPU students since 2009</span>
            </div>
          </div>
        </div>
        <div class="col-lg-7">
          <span class="pill-eyebrow solid">About Us</span>
          <h2 class="mt-2">Built by an HPU family, for HPU families</h2>
          <p class="hero-lede mt-3">Our mission is to give High Point University students and parents a convenient, accountable, and safe moving experience. EPIC was established in 2009 and is locally owned and operated in High Point, NC by High Point University alum Anisha Roy. After a successful corporate career, Anisha set out to run a business of her own — and through the years, we've had the opportunity to serve thousands of HPU students, helping us grow into the company we are today.</p>
          <p class="hero-lede mt-3">We pride ourselves on a high-touch, always-available experience that lets us engage directly with every customer relationship, putting a personal touch on each parent and student's experience.</p>
          <div class="about2-badges">
            <span class="badge2">Established 2009</span>
            <span class="badge2">HPU Class of 1991</span>
            <span class="badge2">Locally Owned, High Point NC</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="testimonials" class="testi2">
    <div class="container">
      <div class="testi2-head">
        <span class="pill-eyebrow">Testimonials</span>
        <h2>What EPIC families say</h2>
      </div>

      <div id="testiCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          <div class="carousel-item active">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="testi2-card">
                  <div class="testi2-photo">
                    <img src="<?= base_url('assets/images/pexels-gustavo-fring-7489129.png') ?>" alt="Two students packing boxes together">
                  </div>
                  <div class="testi2-panel">
                    <p class="testi2-quote">&ldquo;When I called Epic Storage, Anissa was friendly, professional, and above all helpful! They took care of everything for a very reasonable cost. They supply the boxes and tape on campus, and all we needed to do was pack up. They picked everything up and took it to storage, then delivered right to the dorm room when we came back. We're excited to use them again this year!&rdquo;</p>
                    <div class="testi2-who">
                      <span class="testi2-avatar">S</span>
                      <span><strong>Steve</strong><span>Parent From Florida</span></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="testi2-card">
                  <div class="testi2-photo">
                    <img src="<?= base_url('assets/images/pexels-blue-bird-7218507.png') ?>" alt="Man carrying a labeled office box">
                  </div>
                  <div class="testi2-panel">
                    <p class="testi2-quote">&ldquo;Working with Anissa and the EPIC team was an excellent experience from start to finish. Knowing that my kids' stuff was picked up on time and literally waiting in their rooms when we arrived back after summer was worth its weight in gold. She's very responsive, friendly, and knows every nook and cranny of that campus.&rdquo;</p>
                    <div class="testi2-who">
                      <span class="testi2-avatar">J</span>
                      <span><strong>Jen</strong><span>Parent From New Jersey</span></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="row g-4">
              <div class="col-md-6">
                <div class="testi2-card">
                  <div class="testi2-photo">
                    <img src="<?= base_url('assets/images/pexels-silverkblack-23224976.png') ?>" alt="Student moving a packed storage box">
                  </div>
                  <div class="testi2-panel">
                    <p class="testi2-quote">&ldquo;EPIC truly made summer storage for our daughter the best experience possible. Anissa and her friendly staff helped us every step of the way. We felt like VIP customers.&rdquo;</p>
                    <div class="testi2-who">
                      <span class="testi2-avatar">K</span>
                      <span><strong>Kerri</strong><span>Connecticut</span></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="testi2-card">
                  <div class="testi2-photo">
                    <img src="<?= base_url('assets/images/pexels-shvets-production-7203967.png') ?>" alt="Student labeling a box before storage">
                  </div>
                  <div class="testi2-panel">
                    <p class="testi2-quote">&ldquo;EPIC gives every student the &lsquo;White Glove&rsquo; treatment and continued the extraordinary experience HPU prides itself on. They are an asset to HPU families and the University.&rdquo;</p>
                    <div class="testi2-who">
                      <span class="testi2-avatar">J</span>
                      <span><strong>Jim</strong><span>Long Island, NY</span></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="carousel-item">
            <div class="row g-4 justify-content-center">
              <div class="col-md-6">
                <div class="testi2-card">
                  <div class="testi2-photo">
                    <img src="<?= base_url('assets/images/6-1.png') ?>" alt="Parent reviewing storage arrangements online">
                  </div>
                  <div class="testi2-panel">
                    <p class="testi2-quote">&ldquo;Anissa was patient and kind, and she sincerely cared about providing timely and professional service. I'd recommend Epic Storage Solutions for any storage needs — you'll get excellent customer service in the process.&rdquo;</p>
                    <div class="testi2-who">
                      <span class="testi2-avatar">C</span>
                      <span><strong>Carla</strong><span>New Jersey</span></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="carousel-indicators testi2-dots">
          <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
      </div>
    </div>
  </section>

<section class="py-5"><div class="container"><h2>Frequently asked questions</h2><details class="mb-3"><summary>Can I add more items later?</summary><p>Yes. Open your existing order in your account and select Add additional items.</p></details><details class="mb-3"><summary>Do I need to schedule when ordering?</summary><p>You can provide pickup details later from your account.</p></details><a href="<?= site_url('faqs') ?>">View all FAQs</a></div></section>
<?= view('partials/scheduling_info') ?>
</main>
<?= $this->endSection() ?>
