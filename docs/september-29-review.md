# September 29 website review

The local CodeIgniter application implements the requested mobile sizing, branded box artwork, student-focused copy, required registration details, customer/admin profile synchronization, and additions to an existing order. Customer ordering permits one open order per account; completed and cancelled orders remain historical records and allow a new order. Existing records are preserved.

## Admin walkthrough for Garry and Tom

1. Sign in at `/admin/login` with an existing administrator account.
2. Open **Settings → General**. Edit the homepage message title and body. These fields drive the homepage message board. Paste Garry's approved replacement terms into **Current Terms and Conditions**, then save. Plain text and line breaks are preserved and HTML is escaped.
3. Open **Settings → Service pricing**. Change the Summer Storage base price, such as `$500`, shared price per box, and summer school fee. Public package cards and new orders use these settings. Existing orders retain their saved prices; additional boxes retain that order's saved box price, and newly selected storage items use current catalog prices.
4. Open **Users**. Public registrations appear as **Pending email confirmation**. Registration saves student name/email, student ID/phone, parent phone/email, and street/city/state/ZIP. Editing a customer shows the same profile fields, and admin booking details display them.
5. Demonstrate public registration with an authorized test mailbox. The existing registration flow creates a pending account, sends a confirmation link, and activates the account after confirmation. Check SMTP configuration and the actual mailbox to prove delivery; local database and rendering tests do not establish inbox delivery.
6. **Add customer** is an administrator-managed path. Its selected status controls account availability. It does not automatically send the public registration confirmation email. Choose the public registration path when the student must confirm their mailbox. Changing an account to Active manually is an administrative activation, not evidence of mailbox ownership.
7. In the customer portal, open the existing order and choose **Add additional items**. Boxes and catalog items increase its total and balance, preserving its order reference and payments. Summer Advantage retains its existing boxes-only rules. Repeated submission of the same additions form is counted once. Closed orders cannot accept additions.
8. **Date Leaving Campus** uses the existing pickup-date workflow. Scheduling information follows FAQs on public pages; the main navigation scheduling button is removed. The original scheduling URL remains available for existing links.

## Terms and historical records

The original legal text is preserved in a collapsed archive labelled **Previous terms — expired on September 1st, 2016**. The supplied Articles 1-15 document is now imported into the main display. Its source states September 10, 2026 and explicitly labels the agreement a draft for legal review; that notice is preserved and surfaced on the page. Article links and headings improve navigation without changing the wording. New customer orders store the accepted text and acceptance time; both customer and administrator order views expose that snapshot. Old orders are not assigned a new acceptance date or retrospectively changed terms.

## Availability

The former card animation performed timed display steps using page-loaded settings. It did not query live capacity or hold inventory. Service buttons now lead directly to ordering. Server-side booking enablement, date/time, blackout, and capacity validation remain in place.

## Remaining input and external verification

- Confirmation that the supplied draft terms have received the intended legal review before live customer use.
- Confirmed LinkedIn and Facebook destinations; existing links remain pending that input.
- Founder-name confirmation: meeting notes say **Anisha Roy**, while the previous site said **Anissa Roy**. Updated founder copy follows the meeting notes; historical testimonial quotations are preserved.
- Garry/Tom admin walkthrough and actual confirmation-email inbox verification.
- Live deployment and live payment-provider verification are separate from the local implementation.

## Database and verification

Migration `2026-10-05-000001_AddReviewRequirements` adds nullable shipping fields for existing customers, order terms snapshots, an additions request ledger with a unique request token and booking foreign key, and operator-editable messaging/terms settings. Its rollback removes only these additions. The migration is applied to the local application database.

The ordinary PHPUnit configuration uses SQLite, while the existing application migrations contain MySQL-specific statements. Run local verification against an isolated MySQL database with:

```powershell
php vendor/bin/phpunit --bootstrap tests/_support/mysql_bootstrap.php --no-coverage
```

The bootstrap creates a separate process-specific test database from local connection configuration without copying or printing credentials. Public browser results at 375, 768, and 1440 pixels are recorded in `build/review-browser-results.json`; screenshots are in `build/review-home-mobile.png` and `build/review-register-mobile.png`.

Verified locally: 52 PHPUnit tests with 417 assertions passed in the isolated MySQL database. PHP lint and JavaScript syntax checks passed. All 18 responsive public-route checks passed with no horizontal overflow or JavaScript errors. Live email delivery, live Square charges, and deployment were not exercised.

## Supplied content follow-up

The matching Desktop files `images.jpe` and `IMG_4503.jpg` were copied into the project as the university campus and real EPIC-box photographs. Original copies are retained, and optimized WebP versions are used on the website. The campus photograph appears in the homepage About section and About page, with captions that describe the university rather than a founder portrait. The real box photograph replaces the Summer Storage card illustration. The duplicated attachment is used once.

The Word document `EPIC_Storage_Solutions_Terms_and_Conditions_Articles_1-15 09.01.26.docx` was extracted to `app/Resources/terms/2026-09-10-articles-1-15.txt`, preserving text, paragraph breaks, line breaks, and tabs. The date in the document itself is September 10, 2026. Source wording and the draft legal-review notice remain intact.

Migration `2026-10-05-000002_ImportSuppliedTerms` expands settings and order snapshots to MEDIUMTEXT and imports the complete 81,077-byte source when current terms are empty. Existing customized terms are preserved. Admin editing accepts up to 250,000 characters. Rollback restores empty imported terms and shrinks columns only when doing so would preserve all existing records; longer historical snapshots retain their wider storage.

Follow-up verification: the full MySQL suite passed 53 tests with 428 assertions. The 10 focused customer review tests passed 62 assertions, including saving the full document through admin, rendering all 15 article headings, and retaining an exact terms snapshot on a new order. All 18 responsive route checks passed, and article navigation was checked at 375 and 1440 pixels.
