# EPIC Storage revision report - October 15, 2026

## Changes and files

- `public/assets/css/style.css`: shared base font token (17px on phones, scaling to 18px on desktop), one 248 x 56px homepage action style, and a mobile gutter adjustment to prevent overflow with the larger type. Section labels and primary action links use the same sizing; navigation toggles and carousel indicators retain their utility-control sizes.
- `public/assets/css/home.css`: larger stats, three equal process columns (stacked on phones), and matching package-card alignment.
- `app/Views/pages/home.php`, `app/Views/pages/services.php`, new `app/Views/partials/storage_packages.php`: one shared two-card component, side by side from tablet upward and stacked on phones; the two homepage package photos removed. The process subtitle follows its heading, and the steps are Sign up / Pack / Store & return. Stats contain no Founded label. The hero markup and photo are unchanged.
- `app/Views/pages/about.php` and `home.php`: Anisha corrected to Anissa, including founder structured data. Other testimonial names already used Anissa. All templates and local CMS settings were searched; there were no Anisha matches in stored CMS copy. No customer names or legal text were rewritten.
- `app/Views/partials/scheduling_info.php`: retained the section and replaced its body with `[NEW COPY PENDING FROM CLIENT]`. All other scheduling wording remains, apart from requested EPIC capitalization; see the linked inventory.
- `app/Views/layouts/site.php`: existing LinkedIn icon now points to `#linkedin-pending`; Facebook retained. Public/auth/account templates, public controller titles/messages, and `app/Filters/CustomerFilter.php` received visible EPIC capitalization. URLs, identifiers, filenames, legal text, and sender/business-name settings were not renamed.
- `app/Views/auth/register.php`, `app/Views/partials/customer_details.php`, new `public/assets/js/registration.js`: registration hides the entire shipping block; existing student/parent controls are retained. Every remaining field is required. Inline errors cover empty fields, email/phone format, password length, matching passwords, and terms before any POST or loading state.
- `app/Libraries/CustomerDetails.php` and the ordering-prerequisite messages in `app/Controllers/AccountController.php`: shipping is optional server-side and excluded from the completeness gate so a new account can order. All four shipping columns already accept NULL; no migration, column removal, or existing-data change was necessary. Existing profile layout is retained.
- `app/Libraries/CustomerOrderService.php`, `app/Controllers/AccountController.php`: explicitly check persistence of the addition request and log booking ID, failing operation, exception chain, driver code/message, and a stack trace without argument values. Tokens are redacted; customers see a generic failure message. Failed additions remain transactional.
- `tests/feature/CustomerReviewTest.php`: paid/unpaid wardrobe additions, balance display, late-write rollback, registration missing/empty-field checks, registration without shipping, and configured-price regression. `tests/feature/AuthenticationFlowTest.php`: expected EPIC capitalization.

## Verification

- Automated suite: 59 tests / 505 assertions passed against isolated MySQL databases. PHP lint passed for 193 application/test files; JavaScript syntax and `git diff --check` passed.
- Server registration validation was already rejecting absent required fields and passwords. Tests now verify every remaining field with both missing and empty submissions; shipping is accepted empty.
- Paid and unpaid wardrobe additions both passed before the diagnostics change. A paid $500 booking accepted a $70 wardrobe, retained the payment, changed from paid to partial, and showed $70 due with Pay now in Balance Management. An unpaid booking showed $570 due. A simulated late database write failure rolled back the item, total, and payment status and logged database error 1644 with the simulated error message. This is local evidence, not the staging failure's root cause.
- Local CMS audit: homepage title/body, booking message, and active terms body searched; zero Anisha matches. Shipping columns confirmed nullable; target reference EPIC-260922-E01130 absent from the local database.
- Browser checks passed at 375px, 768px and 1440px for eight public routes, inline registration errors before POST, equal 248 x 56px homepage actions, matching Home/Services content, phone stacking and tablet/desktop columns, and paid/unpaid wardrobe additions with Balance Management updates. No JavaScript errors or public-page horizontal overflow were found. Additional short-password and invalid-phone checks passed at all three widths. Results/screenshots are in `build/revision-browser-results.json` and `build/revision-*.png`.
- Source audit confirmed unchanged hero markup and no changes to admin controllers/views, routes, models, or migrations.

## Base price

The local configured Summer Storage base price and a fresh quote are **$500.00**. A new isolated database-backed booking created through the booking endpoint also recorded **$500.00**. The value is read by `app/Libraries/BookingPricingService.php` from `app_settings.setting_key = summer_storage_base_price`. Its code/migration fallback is $450, but the stored $500 value takes precedence. No pricing settings or historical bookings were changed. This verifies the local configuration; staging pricing is not accessible.

## Remaining scheduling wording

[Complete file-and-text inventory](october-15-scheduling-inventory.md) lists the homepage FAQ, Services/FAQs wording, scheduling page, existing portal controls, archived terms, current CMS terms, and search metadata. These references were inventoried without rewriting them.

## External checks and limitations

- Facebook footer URL: [https://www.facebook.com/epicstoragesolutions/](https://www.facebook.com/epicstoragesolutions/). Direct requests returned HTTP 200 and the profile title "Epic Storage Solutions | High Point NC", with no access-restriction notice in the fetched page. The existing working link was retained.
- The supplied `https://staging.epicstoragehpu.com/` returned HTTP 404 during this run. No staging hosting/SSH/SFTP configuration or callable server-log connection was available. The local database has neither booking 10 nor the supplied reference, and the available logs have no matching failure. Therefore the actual staging add-items root cause and its fix remain **unverified**. The paid state alone is not a local cause. Deploy the diagnostic change and obtain the failing staging log before claiming this bug resolved.
- Changes are local; staging deployment, live Square payment collection, and email delivery were not tested.

## Release comparison / unrequested changes

Git contains one commit, `622f846` (Updated, October 15), no tags, and no earlier approved-release marker. The working tree was clean at task entry relative to that snapshot. No unrequested portal/admin differences were present against the available baseline. Comparing with a separately approved release is impossible from this history, so pre-existing features cannot honestly be classified as unrequested. Current portal diffs are restricted to requested visible EPIC spelling, the shipping prerequisite, and add-items diagnostics; admin pages, portal navigation/layout, and the data model were not changed or reverted.
