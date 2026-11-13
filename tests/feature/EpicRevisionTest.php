<?php

declare(strict_types=1);

use App\Libraries\AuthService;
use App\Libraries\BookingPricingService;
use App\Libraries\BookingService;
use App\Libraries\SmtpService;
use App\Libraries\Totp;
use App\Models\BookingModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CustomerFixture;

/**
 * Covers the client-review changes: labels, FAQ, service fee, basket descriptions, admin
 * create-order flow, pre-filled payments, lockout, two-factor login and the activity log.
 *
 * @internal
 */
final class EpicRevisionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public function testFaqHasTheNewPickupAnswerAndNoPlaceholderCopy(): void
    {
        $faq = $this->get('/faqs');
        $faq->assertStatus(200);
        $faq->assertSee('Do I need to be present for pick-up or drop-off?');
        $faq->assertSee('starting the Sunday after graduation');
        $faq->assertDontSee('change my pickup or delivery date');
        foreach (['/', '/services', '/faqs'] as $url) {
            $this->get($url)->assertDontSee('NEW COPY PENDING');
        }
    }

    public function testOrderOptionsExplainWhoEachServiceIsFor(): void
    {
        $form = $this->get('/book');
        $form->assertStatus(200);
        $form->assertSee('Summer Storage (April to August)');
        $form->assertSee('This offering is for current HPU students.');
        $form->assertSee('Summer Advantage (July to August)');
        $form->assertSee('For incoming HPU freshmen attending the Summer Advantage Program.');
    }

    public function testServiceFeeIsAddedToNewQuotesAndSurvivesRecalculation(): void
    {
        db_connect()->table('app_settings')->where('setting_key', 'service_fee_percent')->update(['setting_value' => '3']);

        $quote = (new BookingPricingService())->quote('summer_storage', 0, false, false, []);
        $before = (float) $quote['subtotal'];
        $this->assertSame('3.00', $quote['service_fee_percent']);
        $this->assertEqualsWithDelta(round($before * 0.03, 2), (float) $quote['service_fee'], 0.001);
        $this->assertEqualsWithDelta($before + round($before * 0.03, 2), (float) $quote['total'], 0.001);

        $id = (int) (new BookingModel())->insert([
            'reference_code' => 'EPIC-FEE', 'customer_name' => 'Fee Student', 'customer_email' => 'fee@example.com', 'service_name' => 'Summer Storage',
            'booking_type' => 'summer_storage', 'status' => 'pending', 'payment_status' => 'unpaid', 'base_price' => '500.00',
            'additional_box_quantity' => 2, 'additional_box_amount' => '50.00', 'subtotal' => '0.00', 'discount_amount' => '0.00',
            'service_fee_percent' => '3.00', 'total_amount' => '0.00',
        ], true);
        (new BookingService())->recalculate($id);
        $booking = (new BookingModel())->find($id);

        $this->assertSame('600.00', $booking['subtotal']);
        $this->assertSame('18.00', $booking['service_fee_amount']);
        $this->assertSame('618.00', $booking['total_amount']);

        // Changing the admin percentage later must not re-price an existing order.
        db_connect()->table('app_settings')->where('setting_key', 'service_fee_percent')->update(['setting_value' => '5']);
        (new BookingService())->recalculate($id);
        $this->assertSame('618.00', (new BookingModel())->find($id)['total_amount']);
    }

    public function testAdminCanSetTheServiceFeePercentage(): void
    {
        [, $session] = $this->adminSession();
        $page = $this->withSession($session)->get('/admin/settings');
        $page->assertSee('Credit card service fee');

        $payload = [csrf_token() => csrf_hash(), 'summer_storage_base_price' => '450.00', 'summer_advantage_box_price' => '50.00', 'summer_school_fee' => '75.00', 'service_fee_percent' => '3.5'];
        $this->withSession($session)->post('/admin/settings/pricing', $payload);
        $this->assertSame('3.5', (new \App\Libraries\SettingsService())->get('service_fee_percent'));
    }

    public function testBookingPageDescribesEveryChargeAndOffersAddToOrder(): void
    {
        $userId = $this->customer();
        $bookingId = (int) (new BookingModel())->insert([
            'user_id' => $userId, 'reference_code' => 'EPIC-DESC', 'customer_name' => 'Desc Student', 'customer_email' => 'desc@example.com',
            'service_name' => 'Summer Storage', 'booking_type' => 'summer_storage', 'status' => 'pending', 'payment_status' => 'unpaid',
            'base_price' => '500.00', 'additional_box_quantity' => 0, 'additional_box_amount' => '50.00', 'is_study_abroad' => 1,
            'subtotal' => '0.00', 'discount_amount' => '0.00', 'service_fee_percent' => '3.00', 'total_amount' => '0.00',
        ], true);
        (new BookingService())->recalculate($bookingId);
        $session = ['customer_user_id' => $userId, 'customer_user_name' => 'Desc Student', 'customer_user_email' => 'desc@example.com', 'customer_is_logged_in' => true];

        $page = $this->withSession($session)->get('/account/bookings/' . $bookingId);
        $page->assertStatus(200);
        $page->assertSee('Standard Package (5 lg boxes / Base Package)');
        $page->assertSee('Study abroad: second-semester storage');
        $page->assertSee('Credit card service fee (3%)');
        $page->assertSee('Add to Current Order');

        foreach (['/account', '/account/bookings'] as $url) {
            $this->withSession($session)->get($url)->assertSee('Add to Current Order');
        }
        $this->withSession($session)->get('/account/bookings/' . $bookingId . '/items')->assertStatus(200);
    }

    public function testConfirmationRecipientsAreStudentParentAndSupport(): void
    {
        db_connect()->table('app_settings')->where('setting_key', 'support_email')->update(['setting_value' => 'support@example.com']);
        $method = new ReflectionMethod(SmtpService::class, 'copyRecipients');
        $method->setAccessible(true);

        $copies = $method->invoke(new SmtpService(), ['parent_email_address' => 'Parent@Example.com']);
        $this->assertSame(['Parent@Example.com', 'support@example.com'], $copies);
        $this->assertSame(['support@example.com'], $method->invoke(new SmtpService(), ['parent_email_address' => 'not-an-email']));
        $this->assertSame(['SUPPORT@example.com'], $method->invoke(new SmtpService(), ['parent_email_address' => 'SUPPORT@example.com']));
    }

    public function testAdminCreatesCustomerWithoutConfirmationAndStartsAnOrder(): void
    {
        [, $session] = $this->adminSession();
        $form = $this->withSession($session)->get('/admin/users/new');
        $form->assertSee('Create user &amp; start order');

        $response = $this->withSession($session)->post('/admin/users', [
            csrf_token() => csrf_hash(), ...CustomerFixture::details(),
            'name' => 'Walk In Student', 'email' => 'walkin-student@example.com', 'password' => '', 'password_confirm' => '', 'status' => 'active', 'next' => 'order',
        ]);
        $user = (new UserModel())->where('email', 'walkin-student@example.com')->first();
        $this->assertNotNull($user, 'The customer must be created without a password or email confirmation.');
        $this->assertSame('active', $user['status']);
        $this->assertNotSame('', (string) $user['password_hash']);
        $response->assertRedirectTo('/admin/bookings/new?user=' . $user['id']);

        $order = $this->withSession($session)->get('/admin/bookings/new?user=' . $user['id']);
        $order->assertStatus(200);
        $order->assertSee('Create order');
        $order->assertSee('walkin-student@example.com');
        $order->assertSee('Walk In Student');
        $order->assertSee('HPU12345');
        $order->assertSee('Credit card service fee');
    }

    public function testAdminPaymentAmountIsPrefilledWithTheBalanceOwed(): void
    {
        [, $session] = $this->adminSession();
        $bookingId = (int) (new BookingModel())->insert([
            'reference_code' => 'EPIC-BAL', 'customer_name' => 'Balance Student', 'customer_email' => 'bal@example.com', 'service_name' => 'Storage',
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => '500.00', 'discount_amount' => '0.00', 'total_amount' => '500.00',
        ], true);
        db_connect()->table('transactions')->insert([
            'booking_id' => $bookingId, 'reference' => 'TXN-BAL-1', 'type' => 'payment', 'amount' => '120.00', 'currency' => 'USD', 'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $page = $this->withSession($session)->get('/admin/payments/new?booking=' . $bookingId);
        $page->assertStatus(200);
        $page->assertSee('value="380.00"');
        $page->assertSee('balance $380.00');
    }

    public function testAccountLocksAfterRepeatedFailuresAndUnlocksWhenTheLockExpires(): void
    {
        $email = 'lockout@example.com';
        $id = (int) (new UserModel())->insert(['name' => 'Lock Admin', 'email' => $email, 'password_hash' => password_hash('correct horse battery', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active'], true);
        $auth = new AuthService(null, service('session'));

        for ($i = 0; $i < AuthService::MAX_FAILED_LOGINS; $i++) {
            $this->assertNull($auth->attempt($email, 'wrong password', 'admin', false));
        }
        $this->assertNull($auth->attempt($email, 'correct horse battery', 'admin', false), 'A locked account rejects even the right password.');

        (new UserModel())->update($id, ['locked_until' => date('Y-m-d H:i:s', time() - 60)]);
        $this->assertNotNull($auth->attempt($email, 'correct horse battery', 'admin', false));
    }

    public function testTwoFactorLoginRequiresTheAuthenticatorCode(): void
    {
        $totp = new Totp();
        $secret = $totp->generateSecret();
        $email = uniqid('twofa-', true) . '@example.com';
        $id = (int) (new UserModel())->insert([
            'name' => 'Two Factor Admin', 'email' => $email, 'password_hash' => password_hash('two factor password', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active',
            'totp_secret' => base64_encode(service('encrypter')->encrypt($secret)), 'totp_enabled' => 1,
        ], true);

        $login = $this->post('/admin/login', [csrf_token() => csrf_hash(), 'email' => $email, 'password' => 'two factor password']);
        $login->assertRedirectTo('/admin/login/verify');
        $this->assertNull(session()->get('admin_user_id'), 'The password alone must not sign an admin in.');
        $pending = ['admin_2fa_user_id' => $id, 'admin_2fa_at' => time()];

        $this->withSession($pending)->get('/admin/login/verify')->assertSee('Enter your code');
        $this->withSession($pending)->post('/admin/login/verify', [csrf_token() => csrf_hash(), 'code' => '000000'])->assertRedirect();
        $this->assertNull(session()->get('admin_user_id'));

        $good = $totp->code($secret, intdiv(time(), 30));
        $ok = $this->withSession($pending)->post('/admin/login/verify', [csrf_token() => csrf_hash(), 'code' => $good]);
        $ok->assertRedirectTo('/admin');
        $this->assertSame($id, (int) session()->get('admin_user_id'));
    }

    public function testAdminChangesAreWrittenToTheActivityLog(): void
    {
        [$adminId, $session] = $this->adminSession();
        $bookingId = (int) (new BookingModel())->insert([
            'reference_code' => 'EPIC-LOG', 'customer_name' => 'Log Student', 'customer_email' => 'log@example.com', 'service_name' => 'Storage',
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => '0.00', 'discount_amount' => '0.00', 'total_amount' => '0.00',
        ], true);

        $this->withSession($session)->post('/admin/bookings/' . $bookingId . '/warehouse', [csrf_token() => csrf_hash(), 'row_in_warehouse' => '7']);

        $entry = db_connect()->table('admin_activity_log')->where('admin_user_id', $adminId)->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertNotNull($entry, 'An admin POST must be logged.');
        $this->assertSame('bookings.warehouse', $entry['action']);
        $this->assertSame('bookings/' . $bookingId, $entry['target']);

        $page = $this->withSession($session)->get('/admin/activity-log');
        $page->assertStatus(200);
        $page->assertSee('bookings.warehouse');
    }

    public function testLeavingAndReturningLabelsReplacePickupAndDropoffWording(): void
    {
        [, $session] = $this->adminSession();
        $form = $this->withSession($session)->get('/admin/bookings/new');
        $form->assertSee('Leaving Campus');
        $form->assertSee('Returning to Campus');
        $form->assertDontSee('Date Leaving');
        $form->assertDontSee('Date Returning');
    }

    /** @return array{0: int, 1: array<string, mixed>} */
    private function adminSession(): array
    {
        $id = (int) (new UserModel())->insert([
            'name' => 'Revision Admin', 'email' => uniqid('rev-admin-', true) . '@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active',
        ], true);

        return [$id, ['admin_user_id' => $id, 'admin_user_name' => 'Revision Admin', 'admin_user_email' => 'admin@example.com', 'admin_is_logged_in' => true]];
    }

    private function customer(): int
    {
        return (int) (new UserModel())->insert([
            'name' => 'Desc Student', 'email' => 'desc@example.com', 'password_hash' => password_hash('review password phrase', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', ...CustomerFixture::details(),
        ], true);
    }
}
