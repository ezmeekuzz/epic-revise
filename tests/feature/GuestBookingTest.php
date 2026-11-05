<?php

declare(strict_types=1);

use App\Libraries\BookingLinkService;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Booking without an account, and the rule that a booking joins an account only after
 * ownership of the email is proven (staff, login, email confirmation, or an inbox link).
 *
 * @internal
 */
final class GuestBookingTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public function testGuestCanBookWithoutAnAccountAndOnlyThatSessionCanOpenThePaymentPage(): void
    {
        $result = $this->post('/book', $this->guestPayload('guest@example.com'));

        $booking = (new BookingModel())->where('customer_email', 'guest@example.com')->first();
        $this->assertNotNull($booking);
        $result->assertRedirectTo('/book/' . $booking['id'] . '/pay');
        $this->assertNull($booking['user_id'], 'A booking made without logging in is saved like a walk-in.');
        $this->assertSame('Guest Student', $booking['customer_name']);
        $this->assertSame('336-555-0121', $booking['parent_phone_number']);
        $this->assertSame('parent@example.com', $booking['parent_email_address']);
        $this->assertNotEmpty($booking['terms_snapshot']);

        $access = $_SESSION['guest_booking_access'] ?? [];
        $this->assertContains((int) $booking['id'], $access);
        $this->withSession(['guest_booking_access' => $access])->get('/book/' . $booking['id'] . '/pay')->assertStatus(200);
        $this->withSession(['guest_booking_access' => $access])->get('/book/' . $booking['id'] . '/confirmed')->assertStatus(200);

        // Knowing the booking number is not enough.
        foreach (['/book/' . $booking['id'] . '/pay', '/book/' . $booking['id'] . '/confirmed'] as $url) {
            try {
                $this->withSession(['unrelated' => true])->get($url);
                $this->fail($url . ' must not open without session access.');
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testGuestBookingRequiresContactDetailsAndTerms(): void
    {
        $payload = $this->guestPayload('details@example.com');
        foreach (['customer_name', 'customer_email', 'customer_phone', 'student_id', 'parent_phone_number', 'parent_email_address', 'terms'] as $field) {
            $invalid = $payload;
            unset($invalid[$field]);
            $this->post('/book', $invalid)->assertSessionHas('errors');
            $this->assertArrayHasKey($field, session()->getFlashdata('errors'));
        }
        $this->assertSame(0, (new BookingModel())->where('customer_email', 'details@example.com')->countAllResults());
    }

    public function testRegisteredEmailIsNeverLinkedFromTheFormAndThePageLooksTheSame(): void
    {
        $this->customer('Existing Customer', 'existing@example.com');

        // CodeIgniter shares one response object between test requests, so check each redirect right away.
        $this->post('/book', $this->guestPayload('existing@example.com'))->assertRedirectTo('/book/1/pay');
        $registeredFlash = session()->getFlashdata('success');
        $this->post('/book', $this->guestPayload('newcomer@example.com'))->assertRedirectTo('/book/2/pay');
        $unregisteredFlash = session()->getFlashdata('success');

        $existingBooking = (new BookingModel())->where('customer_email', 'existing@example.com')->first();
        $newBooking = (new BookingModel())->where('customer_email', 'newcomer@example.com')->first();
        $this->assertSame(['1', '2'], [(string) $existingBooking['id'], (string) $newBooking['id']]);
        $this->assertNull($existingBooking['user_id'], 'Typing a registered email must not attach the booking to that account.');
        $this->assertNull($newBooking['user_id']);
        $this->assertSame($registeredFlash, $unregisteredFlash);
        $this->assertNull(session()->get('customer_user_id'), 'Booking with a registered email never logs anyone in.');

        $access = ['guest_booking_access' => [(int) $existingBooking['id'], (int) $newBooking['id']]];
        $normalize = static fn (string $body, array $booking): string => str_replace(
            [$booking['reference_code'], esc(BookingLinkService::maskEmail($booking['customer_email'])), '/book/' . $booking['id'] . '/', '&#x2F;book&#x2F;' . $booking['id'] . '&#x2F;'],
            ['REF', 'EMAIL', '/book/ID/', '/book/ID/'],
            preg_replace('/<!-- DEBUG-VIEW.*?-->|name="csrf_test_name" value="[^"]*"/s', '', $body),
        );
        $existingPage = $normalize((string) $this->withSession($access)->get('/book/' . $existingBooking['id'] . '/confirmed')->response()->getBody(), $existingBooking);
        $newPage = $normalize((string) $this->withSession($access)->get('/book/' . $newBooking['id'] . '/confirmed')->response()->getBody(), $newBooking);
        $this->assertSame($existingPage, $newPage, 'The confirmation page must not reveal whether an email is registered.');
        $this->assertStringContainsString('Already have an EPIC account?', $existingPage);
    }

    public function testClaimLinkFromTheInboxLinksTheBookingAndItsPaymentsAndLogsIn(): void
    {
        $userId = $this->customer('Claiming Customer', 'claim@example.com');
        $bookingId = $this->walkInBooking('claim@example.com');
        $paymentId = (int) (new PaymentModel())->insert([
            'booking_id' => $bookingId, 'user_id' => null, 'reference' => 'PAY-CLAIM-1', 'provider' => 'square', 'method' => 'card',
            'amount' => '25.00', 'currency' => 'USD', 'status' => 'pending',
        ], true);
        $token = (new BookingLinkService())->issueToken($bookingId, BookingLinkService::PURPOSE_CLAIM);
        $this->assertNotNull($token);
        $this->dontSeeInDatabase('booking_access_tokens', ['token_hash' => $token]);

        $result = $this->get('/book/claim/' . $token);
        $result->assertRedirectTo('/account/bookings/' . $bookingId);
        $result->assertSessionHas('customer_user_id', $userId);
        $this->assertSame($userId, (int) (new BookingModel())->find($bookingId)['user_id']);
        $this->assertSame($userId, (int) (new PaymentModel())->find($paymentId)['user_id']);

        // Single use.
        $reused = $this->withSession([])->get('/book/claim/' . $token);
        $reused->assertRedirectTo('/login');
        $reused->assertSessionMissing('customer_user_id');
    }

    public function testClaimNeverMovesABookingThatBelongsToAnotherAccount(): void
    {
        $this->customer('Email Owner', 'owner@example.com');
        $otherId = $this->customer('Other Customer', 'other@example.com');
        $bookingId = $this->walkInBooking('owner@example.com');
        (new BookingModel())->update($bookingId, ['user_id' => $otherId]);
        $token = (new BookingLinkService())->issueToken($bookingId, BookingLinkService::PURPOSE_CLAIM);

        $result = $this->get('/book/claim/' . $token);
        $result->assertRedirectTo('/login');
        $result->assertSessionMissing('customer_user_id');
        $this->assertSame($otherId, (int) (new BookingModel())->find($bookingId)['user_id']);
    }

    public function testMalformedExpiredAndWrongPurposeTokensAreRejected(): void
    {
        $this->customer('Token Customer', 'token@example.com');
        $bookingId = $this->walkInBooking('token@example.com');
        $links = new BookingLinkService();

        $this->get('/book/claim/not-a-token')->assertRedirectTo('/login');
        $this->get('/book/claim/' . str_repeat('a', 64))->assertRedirectTo('/login');

        $payToken = $links->issueToken($bookingId, BookingLinkService::PURPOSE_PAY);
        $this->get('/book/claim/' . $payToken)->assertRedirectTo('/login');

        $expired = $links->issueToken($bookingId, BookingLinkService::PURPOSE_CLAIM);
        db_connect()->table('booking_access_tokens')->where('token_hash', hash('sha256', (string) $expired))->update(['expires_at' => '2020-01-01 00:00:00']);
        $this->get('/book/claim/' . $expired)->assertRedirectTo('/login');

        $this->assertNull((new BookingModel())->find($bookingId)['user_id']);
    }

    public function testPayLinkGrantsAccessToOnlyThatBooking(): void
    {
        $bookingId = $this->walkInBooking('payer@example.com');
        $otherId = $this->walkInBooking('someone@example.com');
        $token = (new BookingLinkService())->issueToken($bookingId, BookingLinkService::PURPOSE_PAY);

        $this->withSession([])->get('/book/pay/' . $token)->assertRedirectTo('/book/' . $bookingId . '/pay');
        $access = $_SESSION['guest_booking_access'] ?? [];
        $this->assertSame([$bookingId], $access);
        $this->assertNotContains($otherId, $access);
        $this->withSession([])->get('/book/pay/' . str_repeat('b', 64))->assertRedirectTo('/contact');
    }

    public function testUnconfirmedAccountsNeverReceiveBookingsUntilTheEmailIsConfirmed(): void
    {
        $userId = (int) (new UserModel())->insert([
            'name' => 'Pending Customer', 'email' => 'pending@example.com', 'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'pending',
        ], true);
        $bookingId = $this->walkInBooking('pending@example.com');

        $this->assertSame(0, (new BookingLinkService())->linkUnclaimed((new UserModel())->find($userId)));
        $this->assertNull((new BookingLinkService())->activeCustomerByEmail('pending@example.com'));
        $this->assertNull((new BookingModel())->find($bookingId)['user_id']);

        $rawToken = bin2hex(random_bytes(32));
        db_connect()->table('email_verification_tokens')->insert([
            'user_id' => $userId, 'token_hash' => hash('sha256', $rawToken), 'redirect_path' => '/account',
            'expires_at' => date('Y-m-d H:i:s', time() + 3600), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->get('/verify-email?token=' . $rawToken)->assertRedirectTo('/account');
        $this->assertSame($userId, (int) (new BookingModel())->find($bookingId)['user_id']);
    }

    public function testLoginLinksUnclaimedBookingsButNeverOwnedOnes(): void
    {
        $userId = $this->customer('Returning Walk-in', 'walkin@example.com');
        $otherId = $this->customer('Somebody Else', 'else@example.com');
        $unclaimed = $this->walkInBooking('walkin@example.com');
        $owned = $this->walkInBooking('walkin@example.com');
        (new BookingModel())->update($owned, ['user_id' => $otherId]);

        $this->post('/login', [csrf_token() => csrf_hash(), 'email' => 'walkin@example.com', 'password' => 'valid password 123'])
            ->assertSessionHas('customer_user_id', $userId);

        $this->assertSame($userId, (int) (new BookingModel())->find($unclaimed)['user_id']);
        $this->assertSame($otherId, (int) (new BookingModel())->find($owned)['user_id']);
    }

    public function testStaffWalkInBookingIsLinkedToAnExistingAccountAutomatically(): void
    {
        $customerId = $this->customer('Walk-in Customer', 'counter@example.com');
        $adminId = (int) (new UserModel())->insert([
            'name' => 'Front Desk', 'email' => 'desk@example.com', 'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'admin', 'status' => 'active',
        ], true);
        $session = ['admin_user_id' => $adminId, 'admin_user_name' => 'Front Desk', 'admin_user_email' => 'desk@example.com', 'admin_is_logged_in' => true];

        foreach (['Counter@Example.com' => $customerId, 'nobody@example.com' => null] as $email => $expected) {
            $this->withSession($session)->post('/admin/bookings', $this->adminPayload($email));
            $booking = (new BookingModel())->where('customer_email', strtolower($email))->first();
            $this->assertNotNull($booking);
            $this->assertSame($expected, $booking['user_id'] === null ? null : (int) $booking['user_id']);
        }

        $form = (string) $this->withSession($session)->get('/admin/bookings/new')->response()->getBody();
        $this->assertStringContainsString('data-customer-emails=', $form);
        $unlinked = (new BookingModel())->where('customer_email', 'nobody@example.com')->first();
        $show = (string) $this->withSession($session)->get('/admin/bookings/' . $unlinked['id'])->response()->getBody();
        $this->assertStringContainsString('Invite to register', $show);
    }

    public function testLoggedInCustomerOpeningThePublicFormIsSentToTheAccountForm(): void
    {
        $userId = $this->customer('Signed In', 'signedin@example.com');
        $this->withSession(['customer_user_id' => $userId, 'customer_is_logged_in' => true])
            ->get('/book?booking_type=summer_advantage')
            ->assertRedirectTo('/account/bookings/new?booking_type=summer_advantage');
    }

    public function testAvailabilityEndpointListsSlotsWithTheServerRules(): void
    {
        $date = (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->modify('+5 days')->format('Y-m-d');
        $payload = json_decode((string) $this->get('/book/availability?date=' . $date)->response()->getBody(), true);
        $this->assertTrue($payload['available']);
        $this->assertNotEmpty($payload['slots']);
        foreach ($payload['slots'] as $slot) {
            $this->assertSame($slot['available'], (new \App\Libraries\BookingAvailabilityService())->validationError($date, $slot['time']) === null);
        }

        db_connect()->table('app_settings')->where('setting_key', 'blackout_dates')->update(['setting_value' => $date]);
        $blocked = json_decode((string) $this->get('/book/availability?date=' . $date)->response()->getBody(), true);
        $this->assertFalse($blocked['available']);
        $this->assertNotEmpty($blocked['message']);

        $invalid = json_decode((string) $this->get('/book/availability?date=not-a-date')->response()->getBody(), true);
        $this->assertFalse($invalid['available']);
    }

    /** @return array<string, string> */
    private function guestPayload(string $email): array
    {
        return [
            csrf_token() => csrf_hash(),
            'booking_type' => 'summer_storage', 'additional_box_quantity' => '0', 'is_summer_school' => '0', 'is_study_abroad' => '0',
            'customer_name' => 'Guest Student', 'customer_email' => $email, 'customer_phone' => '336-555-0120', 'student_id' => 'HPU-GUEST',
            'parent_phone_number' => '336-555-0121', 'parent_email_address' => 'parent@example.com', 'terms' => '1',
        ];
    }

    /** @return array<string, string> */
    private function adminPayload(string $email): array
    {
        return [
            csrf_token() => csrf_hash(), 'user_id' => '', 'customer_name' => 'Walk-in Customer', 'customer_email' => $email,
            'booking_type' => 'summer_storage', 'booking_date' => date('Y-m-d'), 'base_price' => '0.00', 'additional_box_quantity' => '0',
            'additional_box_amount' => '0.00', 'is_study_abroad' => '0', 'is_summer_school' => '0', 'summer_school_fee' => '0.00',
            'status' => 'pending', 'pickup_status' => 'unscheduled', 'dropoff_status' => 'unscheduled', 'discount_amount' => '0.00',
        ];
    }

    private function customer(string $name, string $email): int
    {
        return (int) (new UserModel())->insert([
            'name' => $name, 'email' => $email, 'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', 'email_verified_at' => date('Y-m-d H:i:s'),
        ], true);
    }

    private function walkInBooking(string $email): int
    {
        return (int) (new BookingModel())->insert([
            'reference_code' => 'EPIC-' . strtoupper(bin2hex(random_bytes(4))), 'user_id' => null,
            'customer_name' => 'Walk-in Customer', 'customer_email' => $email, 'service_name' => 'Summer Storage',
            'booking_type' => 'summer_storage', 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal' => '100.00', 'discount_amount' => '0.00', 'total_amount' => '100.00',
        ], true);
    }
}
