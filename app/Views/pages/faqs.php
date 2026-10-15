<?= $this->extend('layouts/site') ?>

<?= $this->section('content') ?>
<main>

  <section class="page-banner has-bg pb-3" style="background-image:url('<?= base_url('assets/images/round-curve.png') ?>');">
    <div class="container banner-inner text-center">
      <span class="eyebrow justify-content-center">Support</span>
      <h1 class="mt-2">Still Have <span class="accent">Questions?</span></h1>
      <p class="hero-lede mx-auto mt-3">We pride ourselves in having a high‑touch and highly available staff to answer your questions. Below you'll find some commonly asked questions. If you still have questions, please call our office at 336.549.5216 — we're happy to help or provide advice on our service offerings and process.</p>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="accordion accordion-brand mx-auto" style="max-width:820px;" id="fullFaq">

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">What makes EPIC a popular choice among HPU students?</button></h2>
          <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#fullFaq"><div class="accordion-body">
            Served 1000s of HPU students since 2009.<br>
            Locally owned and operated by HPU alumni.<br>
            Certified partner of the HPU Administration.<br>
            High‑touch and always‑available team — call us at 336.549.5216.<br>
            Don't take our word for it — look on the HPU parents Facebook page or ask HPU Campus Concierges about us.
          </div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">What is included in the basic package that you provide?</button></h2>
          <div id="q2" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We have found that the typical student uses 5 large EPIC boxes (18"×18"×24"). Included in our package is complimentary tape, pick‑up from your dorm room, secure storage, and delivery back to your new dorm room. You can always add additional boxes or extra items on our services page.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q3">When should I sign up?</button></h2>
          <div id="q3" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">Sign up now with our base package to reserve your spot. You can add additional items and update your pickup schedule later. You will receive an order confirmation email with a link to your order profile where you can make changes and updates.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q4">What if I have additional items that are not listed on your site?</button></h2>
          <div id="q4" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We will accommodate your storage needs. Please call our office at 336.549.5216 to discuss.</div></div>
        </div>

        <div class="accordion-item" id="more-boxes">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q5">Packing suggestions</button></h2>
          <div id="q5" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">
            Boxes should be securely taped on all sides. Anything fragile inside your boxes should be secured properly — do not over‑pack boxes or store food and liquids.<br><br>
            Large furniture or appliances don't need to be boxed, as they're usually too large — we'll supply labels for you upon pick‑up.<br><br>
            Plastic bins or non‑EPIC boxes are treated as a standard box. Beds and furniture must be placed in a nonstandard‑size box and securely taped, or we'll supply labels if items are too large for a box.<br><br>
            Refrigerators must be thoroughly defrosted and cleaned prior to pick up or a defrosting fee applies. Electronics and fragile items need 2–4 inches of packing material on all sides.<br><br>
            All boxes and items must be labeled with your name and a number sequence — our team numbers everything upon pick‑up. Any questions, call our office at 336.549.5216.
          </div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q6">Do I need to be present when you pick up or drop off?</button></h2>
          <div id="q6" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">Whatever is convenient for you — as a certified partner of HPU, we have access to the rooms.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q7">Do you provide packing services?</button></h2>
          <div id="q7" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">Yes, the cost is $150 an hour per staff member. Call our office at 336.549.5216 for details and scheduling.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q8">What if we need additional boxes or need to store non‑boxed items?</button></h2>
          <div id="q8" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">Please call our office at 336.549.5216 and we will arrange to have extra boxes and supplies delivered to your room. For non‑boxed items we will supply labels for you upon pick‑up.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q9">What if I need to change my pickup or delivery date for storage?</button></h2>
          <div id="q9" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">You'll find a link in your order confirmation email that directs you to your profile. Search your email for your student ID to find your order confirmation. You can change the date up to 48 hours before the original scheduled date.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q10">What if I ordered something that we don't need to store?</button></h2>
          <div id="q10" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We will always refund any unused purchases outside of the base package. Please call our office at 336.549.5216 to request the refund.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q11">Where can I find my order confirmation?</button></h2>
          <div id="q11" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">Both the student and the parent are emailed a receipt upon order confirmation with a link that takes you to your order. Search your email for your student ID, or call our office at 336.549.5216.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q12">My student plays a sport or activity — we don't know when they'll be finished for storage pick up.</button></h2>
          <div id="q12" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We will work with each student to accommodate their schedule. Please call our office at 336.549.5216 so we can discuss timing.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q13">What if I need to change rooms after you've delivered my storage items?</button></h2>
          <div id="q13" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We will arrange to have everything moved for you. The cost is $150 an hour per staff member. Please call our office at 336.549.5216 to arrange this service.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q14">What if I decide not to return to HPU?</button></h2>
          <div id="q14" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We will arrange to have your items shipped to you. See our <a href="<?= site_url('shipping') ?>">Shipping</a> page for details and a budgetary cost estimator for shipping via UPS. Call our office at 336.549.5216 to discuss further.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q15">What is the best way to communicate with EPIC?</button></h2>
          <div id="q15" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We are always here to discuss your questions in person. Please call our office at 336.549.5216.</div></div>
        </div>

        <div class="accordion-item">
          <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q16">Do you supply traditional storage options?</button></h2>
          <div id="q16" class="accordion-collapse collapse" data-bs-parent="#fullFaq"><div class="accordion-body">We also have traditional storage units. Please call our office at 336.549.5216 and we can check availability and unit sizes.</div></div>
        </div>

      </div>

      <div class="text-center mt-5">
        <a class="btn btn-brand-primary" href="<?= site_url('services') ?>">Get Started</a>
      </div>
    </div>
  </section>

<?= view('partials/scheduling_info') ?>
</main>
<?= $this->endSection() ?>
