# EPIC Booking Process Plan

**Status:** Built on 2026-11-06. Steps 1–4 are implemented and covered by automated tests. The two open decisions were built with the safe defaults described under [Decisions](#decisions) and can still be changed.

## Goal

Get a customer from **Book** to **paid** in as few steps as possible, without breaking how bookings are tracked, secured and filtered.

| | Today | After |
|---|---|---|
| New customer | About 10 steps and 20 fields. Must leave the site to confirm their email before booking. | **3 steps**, about 8 fields. Never leaves the site. |
| Returning customer | Log in → booking form → confirmation popup → pay | **Form → pay** (logged in) or **Book → form → pay** (not logged in) |
| Walk-in customer | Handled by staff only. Can't see or pay for the booking online. | Registers once, and every booking made with their email appears in their account. |

## Principles

1. **As few steps as possible.** Customers never have to log in or create an account to book and pay.
2. **A booking joins an account only after the email is proven:** by staff in person, by logging in, by an email confirmation, or by a click from the inbox. Typing an email never links anything.
3. **The website never reveals whether an email is registered.** Only the email itself says so.
4. **Bookings without an account work like walk-ins.** They're valid and paid, appear in the admin bookings list, and filter normally. Admin search, filters, Logistics, Schedule and exports all use the booking's own columns, not the account.

## The four ways to book

### A. Customer books online without logging in (main path)

1. Click **Book** on the Services page. The booking form opens directly.
2. Choose the service, boxes and items. Each item and size has **− / +** buttons, so adding one takes a single tap.
3. Enter name, email, student ID, phone, parent phone and parent email. Pickup dorm, room, date and time are optional; times are chosen from the available slots.
4. Tick the terms checkbox next to the button and click **Continue to payment**. There is no confirmation popup.
5. Pay on the payment page by card, **Apple Pay or Google Pay**.
6. The confirmation page looks the same for everyone:
   > Payment received, booking EPIC-1234 confirmed. We emailed your booking details to j\*\*\*@gmail.com.
   > Already have an EPIC account? The email has a button to add this booking to it in one click.
7. The **email** is sent as soon as the booking is saved, so a customer who leaves before paying still has a **Pay now** link. Its account button depends on whether the address already has an account:
   - **Account exists:** "This email already has an EPIC account. **[Add this booking to my account]**". One click links the booking and logs them in.
   - **No account:** "**[Set up my account]**". The email is pre-filled; they choose a password, and the booking moves into the new account automatically.

**3 steps to pay: Book → fill in the form → pay.**

### B. Customer books online while logged in

1. Click **Book**. The form opens with their saved details already filled in.
2. Choose the service and items, then click **Continue**.
3. Pay.

The booking is linked to their account straight away.

### C. Staff enter a walk-in booking

1. An admin creates the booking with the customer's email.
2. **If the email belongs to a confirmed account,** the booking is linked to it automatically. Before saving, the form shows *"Will be linked to: Jamie Cruz"* so staff can catch a mistyped email.
3. **If not,** it stays a walk-in booking. Staff can click **Invite to register**, which emails the customer a sign-up link with their address pre-filled.

### D. A walk-in or no-login customer registers later

1. They sign up with name, email and password only.
2. They confirm their email.
3. **Every booking with that email and no account is moved into their account,** along with its payments and transactions.

## When a booking gets linked to an account

| Situation | Linked… |
|---|---|
| Staff enter it and the email has a confirmed account | Right away |
| Customer books while logged in | Right away |
| Customer books without logging in and the email has an account | When they click **Add to my account** in the email |
| Customer books without logging in and the email has no account | When they register and confirm the email |
| Any unlinked booking with their email | Also whenever they log in (backup) |

A booking that already belongs to an account is never moved to another one.

**If the customer ignores the email,** the booking still works like a walk-in booking: it's valid and paid, and staff can see and manage it. Booking confirmations, pickup reminders and balance-due emails for that booking also include the **Add to my account** button.

**Why the website doesn't just say "this email is already registered":** anyone could type a classmate's email to find out whether they use EPIC, or be logged in to someone else's account just by typing their email. Only the owner of the address receives the email, so that is where this information goes.

## Security safeguards (required)

1. **Link tokens** are long and random, stored only as a hash, usable once, expire after a few days, and each covers a single booking only.
2. **The website responds identically** for registered and unregistered emails: same wording, same page, masked address.
3. **No account details are shown or pre-filled** for customers who aren't logged in.
4. **Bookings are only linked to confirmed accounts,** and only if they don't already belong to an account.
5. **Customers who aren't logged in can pay only through their own session** (right after booking) **or a secure email link** (for later balances). A booking ID number alone never gives access.
6. **Rate limits** on bookings made without logging in and on resending emails.
7. **Linking updates the booking, payment and transaction records together,** so nothing is left half-linked.
8. **Tests for attacks:** wrong or already-used tokens, unconfirmed accounts, bookings already owned by another account, and identical website responses for registered and unregistered emails.

### What this does not protect against

- **Someone who controls the customer's email inbox** can link bookings. The same is true of any "forgot password" link, so this is the normal level of protection on the web.
- **Someone typing another person's email** can make that person receive a receipt email for a booking the typist made and paid for. Nothing is linked unless the owner clicks, so the worst outcome is an unwanted email, and the rate limits keep that in check.
- **Staff entering the wrong email** on a walk-in booking would link it to the wrong account. The *"Will be linked to: …"* preview is there to catch this.

## Build steps

Each step includes tests, and the full test suite is run before moving to the next one.

### Step 1: Quick wins

- Remove the "Continue to payment?" confirmation popup from the booking form (`app/Views/account/booking_form.php`).
- **Book** opens the booking form directly instead of the login page.
- Item picker: list each item and size with **− / +** buttons (`app/Views/account/booking_form.php`, `public/assets/js/booking-processing.js`).
- Sign-up asks only for name, email and password, with a **Show** button on the password field instead of a "Confirm password" field (`app/Views/auth/register.php`, `app/Controllers/AuthController.php`).

### Step 2: Booking without logging in

- The booking form and payment page work without an account.
- New booking columns for **parent phone and parent email**, so the admin booking page still shows them when there's no account. Today that section is read from the customer's account.
- Payment access for customers who aren't logged in: through their session right after booking, or through a secure email link for later balances.

### Step 3: Linking bookings to accounts

- One service that links bookings to an account by email, used in every case listed in [When a booking gets linked](#when-a-booking-gets-linked-to-an-account).
- Email template with **Add to my account** and **Set up my account** buttons.
- **Invite to register** button and the *"Will be linked to: …"* preview on the admin booking form.
- The **Add to my account** button also appears in confirmation, reminder and balance-due emails.

### Step 4: Easier pickup times and payment

- Pickup time slots shown as buttons, with full slots greyed out, so customers don't find out a slot is full only after submitting.
- Apple Pay and Google Pay through Square. This needs domain verification in the Square dashboard.

## Decisions

These were open when the plan was agreed. They were built with the safer option, and either can be changed later.

1. **Terms checkbox.** The booking-form checkbox was **moved next to the Continue button**; it is still required, and the accepted terms are still saved with each order. The alternative (keep only the payment page's "I authorize… and agree to the Terms" checkbox) needs sign-off from whoever owns the terms before the booking-form checkbox is removed.

2. **Customers with more than one open order.** **Allowed.** A customer who doesn't log in can create a second order even if they already have an open one, because the website can't check without revealing that the account exists. Staff merge or cancel duplicates, which should be rare. The alternative (add a linked booking's items to the existing open order) was not built.

3. **Email confirmation.** Booking no longer needs an account, so nobody has to confirm an email before booking. Creating an account still requires email confirmation, and walk-in bookings move into an account only after that confirmation.

## Setup notes

- **Database:** run `php spark migrate` on each server. The migration adds parent contact columns to bookings and a `booking_access_tokens` table.
- **Apple Pay:** register the site's domain in the Square Developer Dashboard. Until then the Apple Pay button simply doesn't appear; card and Google Pay work without it.
- **Email:** the "booking received" and "invite to register" emails use the existing SMTP settings. If SMTP is off, bookings still save; staff can resend from the admin booking page.
