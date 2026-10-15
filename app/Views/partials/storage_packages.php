<?php
/** Shared Home/Services cards use the configured catalog prices. */
$summerStoragePrice = number_format((float) ($pricingSettings['summer_storage_base_price'] ?? 450), 2);
$summerAdvantageBoxPrice = number_format((float) ($pricingSettings['summer_advantage_box_price'] ?? 50), 2);
?>
      <div class="row g-4">
        <div class="col-md-6" data-plan-card>
          <div class="plan-card">
            <h2 class="h4">Summer Storage</h2>
            <span class="plan-window">April to August</span>
            <p class="fw-bold mt-3 mb-0">This offering is for current HPU students.</p>
            <div class="plan-price-row">
              <span class="plan-price">$<?= esc($summerStoragePrice) ?></span>
              <span class="plan-price-note">standard package</span>
            </div>
            <ul class="plan-bullets">
              <li>5 Large Boxes (18"×18"×24")</li>
              <li>Packing tape and supplies</li>
              <li>Secured storage</li>
              <li>Pickup from your dorm</li><li>Re-delivery to your dorm</li>
            </ul>
            <div class="plan-notes">
              <span>Additional boxes and items can be added</span>
              <span>Shipping services are available</span>
              <span>Summer school and study abroad options</span>
            </div>

            <a class="btn btn-brand-primary w-100 mt-4<?= ! empty($homePackageCards) ? ' home-action' : '' ?>" href="<?= site_url('account/bookings/new?booking_type=summer_storage') ?>">Let's Get Started</a>
          </div>
        </div>

        <div class="col-md-6" data-plan-card>
          <div class="plan-card">
            <h2 class="h4">Summer Advantage</h2>
            <span class="plan-window">July to August</span>
            <p class="fw-bold mt-3 mb-0">For incoming HPU freshmen attending the Summer Advantage Program.</p>
            <div class="plan-price-row">
              <span class="plan-price">$<?= esc($summerAdvantageBoxPrice) ?></span>
              <span class="plan-price-note">per large box (18"×18"×24")</span>
            </div>
            <p class="fw-bold mb-1 mt-3">Order per box &amp; includes:</p>
            <ul class="plan-bullets">
              <li>Packing tape and supplies</li>
              <li>Secured storage</li>
              <li>Pickup from your dorm</li><li>Re-delivery to your dorm</li>
            </ul>
            <div class="plan-notes">
              <span>Additional items can be added</span>
              <span>Shipping services are available</span>
            </div>

            <a class="btn btn-brand-primary w-100 mt-4<?= ! empty($homePackageCards) ? ' home-action' : '' ?>" href="<?= site_url('account/bookings/new?booking_type=summer_advantage') ?>">Let's Get Started</a>
          </div>
        </div>
      </div>
