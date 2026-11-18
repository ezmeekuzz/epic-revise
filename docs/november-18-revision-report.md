# EPIC Storage revision report - November 18, 2026

## Changes and files

- `app/Libraries/SmtpService.php`, `app/Libraries/BookingNotifier.php`: order notices (booking received, booking created, payment received, items added, schedule updated, guest booking received) go to the student. The parent/guardian and EPIC support each receive their own `Copy:` message, sent as a separate message so the student's address is not exposed to other recipients. Copies are built by `recordCopyParagraphs()`, which drops any sentence that mentions a button, link, click or password, states who the original went to, and carries no call-to-action buttons. They contain no one-click login, claim or payment links to the student's account. A copy is skipped when its address is empty or equal to the student's, and an invalid parent address is ignored. Each copy is logged as `<purpose>_copy`.
- `app/Controllers/Admin/BookingsController.php`: an order created by staff now sends the student confirmation immediately (with link-free copies to parent and support) instead of waiting for a manual resend. The success message says so. Manual resend from the booking page is unchanged. The booking list used for bulk notifications now loads up to 1000 bookings instead of 100.
- `app/Libraries/Totp.php`, `app/Controllers/Admin/AuthController.php`, `app/Controllers/Admin/TwoFactorController.php`, `app/Models/UserModel.php`, new migration `2026-11-18-000001_AddTotpLastStep.php`: new `matchedStep()` returns the 30-second step a code belongs to. The last accepted step is stored per user in `totp_last_step`, and a code whose step is not newer is rejected, so a code cannot be replayed. Setup consumes its own code, and turning two-factor off requires a current unused code and clears the stored step. `verify()` is kept and now wraps `matchedStep()`.
- `app/Libraries/AuthService.php`: failed sign-ins are counted per email and IP address (`attemptKey()`, 15-minute window), so a stranger cannot lock the real owner out. A separate, higher account-wide limit (`MAX_ACCOUNT_FAILURES = 20`) still locks the account against guessing spread over many addresses. A successful sign-in clears the address counter. Locks remain silent (same result as a wrong password).
- `app/Filters/AdminFilter.php`, `app/Libraries/ActivityLog.php`, new migration `2026-11-18-000002_AddActivityOutcome.php`, `app/Controllers/Admin/PaymentsController.php`: the admin activity log records an `outcome` (`ok` or `rejected`). A request is `rejected` when it returns HTTP 400 or higher, or redirects back with an error, errors or an error/warning alert. Previously a refused change that redirected with HTTP 302 was logged as a normal success.
- `app/Controllers/Admin/ActivityLogController.php`, `app/Views/admin/activity_log/index.php`, `app/Config/Routes.php`: the log page shows the outcome column, search also matches outcome, and a new `activity-log/export.csv` route exports every matching row (up to 50,000) for audits. Cells that begin with `=`, `+`, `-` or `@` are prefixed with an apostrophe to prevent spreadsheet formula injection.
- `public/assets/js/booking-processing.js`, `public/assets/css/booking-flow.css`: selected state for binary-choice rows and checkboxes is now applied through an `is-selected` class (kept in sync on load and on change) for browsers without `:has()` support. In `booking-flow.css` the choice controls now use shared tokens (`--choice-ring`, `--choice-ring-soft`, `--choice-border`) with a `color-mix()` upgrade under `@supports`, a darker unchecked border, disabled-state styling (no hover, not-allowed cursor), invalid styling only while unchecked, a `forced-colors` (high-contrast) fallback using system `Highlight`, and reduced-motion coverage for the choice controls. Styles stay scoped to `[data-booking-processing-form]`.
- `tests/feature/EpicRevisionTest.php`: added `testParentAndSupportCopiesNeverCarryAccountLinks`, `testAccountLocksWhenManyDifferentAddressesKeepGuessing` and `testAdminCreatedOrderSendsTheConfirmationAndStillSaves`.

## Verification

- PHP lint passed for all 16 PHP files changed in the November 18 commit. JavaScript syntax check passed for `booking-processing.js`. `git diff --check` reported no whitespace errors.
- Booking #6 (`EPIC-261118-0261EC`, created 03:39) was traced through the application log. Every message was accepted by the SMTP server with no errors: student booking received, parent copy, support copy, admin alert, student payment receipt and the parent and support payment copies. This confirms the notifier, copy and link-free paths run in the live flow.
- The reported "no email received" was traced end to end: SMTP settings in `app_settings` are enabled and complete (host `mail.riftofheroes.com`, port 587, TLS), and the application log shows acceptance for every recipient. The messages were received once the correct mailbox was checked, so no application defect was found.
- The `RuntimeException` email entries dated November 17, 21:56-21:58 were matched to PHPUnit runs, where SMTP is disabled by design. They are not production failures.
- Migration status was checked: `AddTotpLastStep` and `AddActivityOutcome` are **pending** on this database. The log shows `Unknown column 'outcome'` at 03:49 when an admin login tried to write its activity entry. The application continued, but that login was not recorded.

## Email delivery findings

DNS for the sending domain was queried through 8.8.8.8:

- SPF for `riftofheroes.com` is `v=spf1 a mx ptr include:secureserver.net ~all`. It uses the deprecated `ptr` mechanism and ends in a soft fail.
- No DMARC record exists at `_dmarc.riftofheroes.com`.
- No DKIM record exists at `default._domainkey.riftofheroes.com`. Only that selector was checked, so DKIM under another selector is not ruled out.
- `mail.riftofheroes.com` resolves to `132.148.176.113`.

Mail from this domain was delivered in the current test, but weak authentication makes spam-folder placement or quarantine likely for stricter recipients such as the `epicstoragehpu.com` mailbox. Recommended: enable DKIM on the mail host, tighten SPF to name the sending server, publish a DMARC record (start with `p=none` and a reporting address), and plan a move to a sending domain EPIC controls.

## External checks and limitations

- The automated suite could not be run in this session. The PHP `sqlite3` extension is not loaded, so 83 of 91 tests error in database setup before reaching application code (`Tests: 91, Assertions: 30, Errors: 83`). No pass count is claimed for the three new tests. They need to be run in an environment with the extension enabled.
- The two pending migrations have not been applied. Run `php spark migrate`. Until then, TOTP replay protection cannot store its step and the activity log cannot record outcomes.
- Inbox placement was confirmed by the user for one mailbox only. Delivery to the parent and support mailboxes, spam placement and quarantine behaviour were not independently tested.
- DNS and mail-header results describe the state at the time of the check and may change when records are edited.
- Staging deployment, live Square payment collection and browser checks at 375px, 768px and 1440px were not run for the booking-flow style change.

## Unrequested changes

None. Changes are limited to the email copy behaviour, admin sign-in and two-factor hardening, activity-log outcomes and export, and the selected-state fallback in the booking flow. Public pricing, routes other than the CSV export, and the data model apart from the two new nullable-column migrations were not changed.
