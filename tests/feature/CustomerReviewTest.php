<?php

declare(strict_types=1);

use App\Libraries\CustomerOrderService;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class CustomerReviewTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public static function details(): array
    {
        return \Tests\Support\CustomerFixture::details();
    }

    public function testRegistrationRequiresAndPersistsAllCustomerDetails(): void
    {
        $base = ['name' => 'Review Student', 'email' => 'review@example.com', 'password' => 'review password phrase', 'password_confirm' => 'review password phrase', 'terms' => '1'];
        $invalid = $this->post('/register', [csrf_token() => csrf_hash(), ...$base]);
        $invalid->assertSessionHas('errors');
        $this->assertSame(0, (new UserModel())->where('email', $base['email'])->countAllResults());
        $valid = $this->post('/register', [csrf_token() => csrf_hash(), ...$base, ...self::details()]);
        $valid->assertRedirectTo('/verify-email');
        $user = (new UserModel())->where('email', $base['email'])->first();
        $this->assertNotNull($user);
        foreach (self::details() as $field => $value) {
            $this->assertSame($value, $user[$field]);
        }
    }

    public function testAdditionsKeepOneOrderAndAreIdempotent(): void
    {
        [$userId, $bookingId] = $this->order();
        $token = bin2hex(random_bytes(32));
        $orders = new CustomerOrderService();
        $orders->add($userId, $bookingId, 2, [], $token);
        $orders->add($userId, $bookingId, 2, [], $token);
        $booking = (new BookingModel())->find($bookingId);
        $this->assertSame(2, (int) $booking['additional_box_quantity']);
        $this->assertSame('560.00', number_format((float) $booking['total_amount'], 2, '.', ''));
        $this->assertSame(1, (new BookingModel())->where('user_id', $userId)->countAllResults());
    }

    public function testOrderOwnershipAndClosedOrdersAreEnforced(): void
    {
        [$userId, $bookingId] = $this->order();
        (new BookingModel())->update($bookingId, ['status' => 'completed']);
        $this->expectException(InvalidArgumentException::class);
        (new CustomerOrderService())->add($userId, $bookingId, 1, [], bin2hex(random_bytes(32)));
    }

    public function testExistingOrderRedirectsNewCheckoutAndDashboardShowsParentDetails(): void
    {
        [$userId, $bookingId] = $this->order();
        $session = ['customer_user_id' => $userId, 'customer_user_name' => 'Review Student', 'customer_user_email' => 'review@example.com', 'customer_is_logged_in' => true];
        $this->withSession($session)->get('/account/bookings/new')->assertRedirectTo('/account/bookings/' . $bookingId . '/items');
        $dashboard = $this->withSession($session)->get('/account');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('parent@example.com');
        $dashboard->assertSee('123 Example Street');
        $this->withSession($session)->get('/account/bookings/' . $bookingId . '/items')->assertStatus(200);
    }

    public function testTermsArchiveAndRealOrderingLinksRender(): void
    {
        $this->get('/terms')->assertSee('expired on September 1st, 2016');
        $services = $this->get('/services');
        $services->assertStatus(200);
        $this->assertStringNotContainsString('Checking live availability', (string) $services->response()->getBody());
        $services->assertSee('Scheduling your pickup');
    }

    public function testDuplicateCheckoutCannotCreateAnotherOrderAndTermsAreSnapshotted(): void
    {
        [$userId, $oldId] = $this->order();
        (new BookingModel())->update($oldId, ['status' => 'completed']);
        db_connect()->table('app_settings')->where('setting_key', 'current_terms_text')->update(['setting_value' => 'Approved review terms.']);
        $session = ['customer_user_id' => $userId, 'customer_user_name' => 'Review Student', 'customer_user_email' => 'review@example.com', 'customer_is_logged_in' => true];
        $payload = ['booking_type' => 'summer_storage', 'additional_box_quantity' => '0', 'terms' => '1'];
        $first = $this->withSession($session)->post('/account/bookings', [csrf_token() => csrf_hash(), ...$payload]);
        $active = (new CustomerOrderService())->activeOrder($userId);
        $this->assertNotNull($active);
        $first->assertRedirectTo('/account/bookings/' . $active['id'] . '/payment');
        $this->assertSame('Approved review terms.', $active['terms_snapshot']);
        $this->assertNotEmpty($active['terms_accepted_at']);
        $this->withSession($session)->post('/account/bookings', [csrf_token() => csrf_hash(), ...$payload])->assertRedirectTo('/account/bookings/' . $active['id'] . '/items');
        $this->assertSame(2, (new BookingModel())->where('user_id', $userId)->countAllResults());
    }

    public function testItemAdditionsRetainPaymentsAndApplyStudyAbroadCharges(): void
    {
        [$userId, $bookingId] = $this->order();
        (new BookingModel())->update($bookingId, ['is_study_abroad' => 1]);
        (new \App\Models\TransactionModel())->insert(['booking_id' => $bookingId, 'reference' => 'REVIEW-PAID', 'type' => 'payment', 'amount' => '500.00', 'status' => 'completed', 'currency' => 'USD']);
        $size = (new \App\Models\ItemSizeModel())->where('unit_price', '70.00')->first();
        $this->assertNotNull($size);
        (new CustomerOrderService())->add($userId, $bookingId, 1, [['size_id' => $size['id'], 'quantity' => 2]], bin2hex(random_bytes(32)));
        $updated = (new BookingModel())->find($bookingId);
        $this->assertSame(1340.0, (float) $updated['total_amount']);
        $this->assertSame('partial', $updated['payment_status']);
        $this->assertSame(1, (new \App\Models\TransactionModel())->where('booking_id', $bookingId)->countAllResults());
        $this->assertSame(140.0, (float) (new BookingItemModel())->where('booking_id', $bookingId)->first()['line_total']);
    }

    public function testForeignOrderCannotBeModifiedAndInvalidItemsRollBack(): void
    {
        [$userId, $bookingId] = $this->order();
        $otherId = (int) (new UserModel())->insert(['name' => 'Other Student', 'email' => 'other@example.com', 'password_hash' => password_hash('review password phrase', PASSWORD_DEFAULT), 'role' => 'customer', 'status' => 'active'], true);
        foreach ([[$otherId, []], [$userId, [['size_id' => 999999, 'quantity' => 1]]]] as [$owner, $items]) {
            try {
                (new CustomerOrderService())->add($owner, $bookingId, 2, $items, bin2hex(random_bytes(32)));
                $this->fail('An invalid addition should be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertSame(0, (int) (new BookingModel())->find($bookingId)['additional_box_quantity']);
            }
        }
        $this->assertSame(0, (new BookingItemModel())->where('booking_id', $bookingId)->countAllResults());
    }

    public function testAdminMessagingTermsAndCustomerDetailsStaySynchronized(): void
    {
        [$userId, $bookingId] = $this->order();
        $adminId = (int) (new UserModel())->insert(['name' => 'Review Admin', 'email' => 'admin-review@example.com', 'password_hash' => password_hash('review password phrase', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active'], true);
        $session = ['admin_user_id' => $adminId, 'admin_user_name' => 'Review Admin', 'admin_user_email' => 'admin-review@example.com', 'admin_is_logged_in' => true];
        $general = (new \App\Libraries\SettingsService())->displayGroup('general');
        $saved = $this->withSession($session)->post('/admin/settings/general', [csrf_token() => csrf_hash(), ...$general, 'homepage_message_title' => 'Student storage announcement', 'homepage_message_body' => 'Reservations open for our next season.', 'current_terms_text' => 'Approved terms <script>example</script>']);
        $saved->assertRedirect();
        $saved->assertSessionHas('success');
        $this->get('/')->assertSee('Student storage announcement');
        $terms = $this->get('/terms');
        $this->assertStringContainsString('&lt;script&gt;example&lt;/script&gt;', (string) $terms->response()->getBody());
        $this->withSession($session)->get('/admin/users/' . $userId . '/edit')->assertSee('parent@example.com');
        $order = $this->withSession($session)->get('/admin/bookings/' . $bookingId);
        $order->assertStatus(200);
        $order->assertSee('123 Example Street');
        $order->assertSee('parent@example.com');
    }

    public function testSuppliedTermsAreSavedRenderedAndSnapshottedInFull(): void
    {
        $source = file_get_contents(APPPATH . 'Resources/terms/2026-09-10-articles-1-15.txt');
        $this->assertIsString($source);
        $this->assertGreaterThan(65535, strlen($source));
        $this->assertSame($source, (new \App\Libraries\SettingsService())->get('current_terms_text'));
        $adminId = (int) (new UserModel())->insert(['name' => 'Terms Admin', 'email' => 'terms-admin@example.com', 'password_hash' => password_hash('review password phrase', PASSWORD_DEFAULT), 'role' => 'admin', 'status' => 'active'], true);
        $adminSession = ['admin_user_id' => $adminId, 'admin_user_name' => 'Terms Admin', 'admin_user_email' => 'terms-admin@example.com', 'admin_is_logged_in' => true];
        $general = (new \App\Libraries\SettingsService())->displayGroup('general');
        $this->withSession($adminSession)->post('/admin/settings/general', [csrf_token() => csrf_hash(), ...$general, 'current_terms_text' => $source])->assertSessionHas('success');
        $this->assertSame($source, (new \App\Libraries\SettingsService())->get('current_terms_text'));
        $response = $this->get('/terms');
        $response->assertStatus(200);
        $this->assertSame(15, preg_match_all('/id="terms-article-\d+"/', (string) $response->response()->getBody()));
        $response->assertSee('LEGAL REVIEW NOTICE:');
        $response->assertSee('expired on September 1st, 2016');
        [$userId, $oldId] = $this->order();
        (new BookingModel())->update($oldId, ['status' => 'completed']);
        $session = ['customer_user_id' => $userId, 'customer_user_name' => 'Review Student', 'customer_user_email' => 'review@example.com', 'customer_is_logged_in' => true];
        $this->withSession($session)->post('/account/bookings', [csrf_token() => csrf_hash(), 'booking_type' => 'summer_storage', 'additional_box_quantity' => '0', 'terms' => '1']);
        $active = (new CustomerOrderService())->activeOrder($userId);
        $this->assertNotNull($active);
        $this->assertSame($source, $active['terms_snapshot']);
    }


    public function testPaidBookingAcceptsWardrobeAndShowsNewBalance(): void
    {
        $this->assertWardrobeAddition(true);
    }

    public function testUnpaidBookingAcceptsWardrobeAndShowsNewBalance(): void
    {
        $this->assertWardrobeAddition(false);
    }

    private function assertWardrobeAddition(bool $paid): void
    {
        [$userId, $bookingId] = $this->order();
        if ($paid) {
            (new \App\Libraries\PaymentService())->create([
                'booking_id' => $bookingId, 'user_id' => $userId, 'reference' => 'WARDROBE-PAYMENT',
                'provider' => 'manual', 'method' => 'cash', 'amount' => '500.00', 'currency' => 'USD', 'status' => 'completed',
            ]);
        }
        $this->assertSame($paid ? 'paid' : 'unpaid', (new BookingModel())->find($bookingId)['payment_status']);
        $size = db_connect()->table('item_sizes')->select('item_sizes.id,item_sizes.unit_price')->join('items', 'items.id=item_sizes.item_id')->like('items.name', 'wardrobe')->orderBy('item_sizes.unit_price', 'ASC')->get()->getRowArray();
        $this->assertNotNull($size);
        $token = bin2hex(random_bytes(32));
        $session = ['customer_user_id' => $userId, 'customer_is_logged_in' => true, 'order_addition_' . $bookingId => $token];
        $result = $this->withSession($session)->post('/account/bookings/' . $bookingId . '/items', [
            csrf_token() => csrf_hash(), 'request_token' => $token, 'additional_boxes' => '0', 'quantities' => [$size['id'] => '1'],
        ]);
        $result->assertRedirectTo('/account/bookings/' . $bookingId);
        $booking = (new BookingModel())->find($bookingId);
        $expectedTotal = 500 + (float) $size['unit_price'];
        $this->assertSame($expectedTotal, (float) $booking['total_amount']);
        $this->assertSame($paid ? 'partial' : 'unpaid', $booking['payment_status']);
        $this->assertSame(1, (new BookingItemModel())->where('booking_id', $bookingId)->countAllResults());
        $this->assertSame($paid ? 1 : 0, (new \App\Models\TransactionModel())->where('booking_id', $bookingId)->countAllResults());
        $balance = $this->withSession($session)->get('/account/balance');
        $balance->assertStatus(200);
        $balance->assertSee('$' . number_format($expectedTotal - ($paid ? 500 : 0), 2));
        $balance->assertSee('Pay now');
    }

    public function testRegistrationWithoutShippingCanOrderAfterVerification(): void
    {
        $details = array_filter(self::details(), static fn (string $field): bool => ! str_starts_with($field, 'shipping_'), ARRAY_FILTER_USE_KEY);
        $result = $this->post('/register', [
            csrf_token() => csrf_hash(), ...$details, 'name' => 'No Shipping Student', 'email' => 'no-shipping@example.com',
            'password' => 'a unique registration passphrase', 'password_confirm' => 'a unique registration passphrase', 'terms' => '1',
        ]);
        $result->assertRedirectTo('/verify-email');
        $user = (new UserModel())->where('email', 'no-shipping@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(\App\Libraries\CustomerDetails::complete($user));
        $this->assertEmpty($user['shipping_street']);
        (new UserModel())->update($user['id'], ['status' => 'active', 'email_verified_at' => date('Y-m-d H:i:s')]);
        $this->withSession(['customer_user_id' => $user['id'], 'customer_is_logged_in' => true])->get('/account/bookings/new')->assertStatus(200);
    }

    public function testRegistrationRejectsEveryMissingRequiredField(): void
    {
        $payload = [...self::details(), 'name' => 'Required Student', 'email' => 'required@example.com', 'password' => 'a unique registration passphrase', 'password_confirm' => 'a unique registration passphrase', 'terms' => '1'];
        foreach (['name', 'email', 'student_id', 'phone_number', 'parent_phone_number', 'parent_email_address', 'password', 'password_confirm', 'terms'] as $field) {
            foreach ([null, ''] as $empty) {
                $invalid = $payload;
                if ($empty === null) { unset($invalid[$field]); } else { $invalid[$field] = ''; }
                $this->post('/register', [csrf_token() => csrf_hash(), ...$invalid])->assertSessionHas('errors');
                $this->assertArrayHasKey($field, session()->getFlashdata('errors'));
            }
        }
        $this->assertSame(0, (new UserModel())->where('email', $payload['email'])->countAllResults());
    }


    public function testNewBookingUsesConfiguredPriceWithoutRepricingExistingOrder(): void
    {
        [$userId, $existingId] = $this->order();
        (new BookingModel())->update($existingId, ['status' => 'completed']);
        db_connect()->table('app_settings')->where('setting_key', 'summer_storage_base_price')->update(['setting_value' => '500.00']);
        $result = $this->withSession(['customer_user_id' => $userId, 'customer_is_logged_in' => true])->post('/account/bookings', [
            csrf_token() => csrf_hash(), 'booking_type' => 'summer_storage', 'additional_box_quantity' => '0', 'terms' => '1',
        ]);
        $created = (new CustomerOrderService())->activeOrder($userId);
        $this->assertNotNull($created);
        $result->assertRedirectTo('/account/bookings/' . $created['id'] . '/payment');
        $this->assertSame(500.0, (float) $created['base_price']);
        $this->assertSame(500.0, (float) (new BookingModel())->find($existingId)['base_price']);
    }

    public function testLateDatabaseFailureRollsBackItemsAndPreservesPaidBalance(): void
    {
        $db = db_connect();
        if ($db->DBDriver !== 'MySQLi') {
            $this->markTestSkipped('The deployment database failure regression requires MySQL SIGNAL.');
        }
        [$userId, $bookingId] = $this->order();
        (new \App\Libraries\PaymentService())->create([
            'booking_id' => $bookingId, 'user_id' => $userId, 'reference' => 'ROLLBACK-PAYMENT',
            'provider' => 'manual', 'method' => 'cash', 'amount' => '500.00', 'currency' => 'USD', 'status' => 'completed',
        ]);
        $size = (new \App\Models\ItemSizeModel())->first();
        $trigger = $db->protectIdentifiers($db->prefixTable('revision_fail_addition'));
        $table = $db->protectIdentifiers($db->prefixTable('booking_additions'));
        $db->query('CREATE TRIGGER ' . $trigger . ' BEFORE INSERT ON ' . $table . " FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'REVISION_TEST: simulated request persistence failure'");
        try {
            try {
                (new CustomerOrderService())->add($userId, $bookingId, 0, [['size_id' => $size['id'], 'quantity' => 1]], bin2hex(random_bytes(32)));
                $this->fail('A failed database write must not be reported as successful.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('additional-item request', $exception->getMessage());
            }
        } finally {
            $db->query('DROP TRIGGER ' . $trigger);
            $db->resetTransStatus();
        }
        $booking = (new BookingModel())->find($bookingId);
        $this->assertSame(500.0, (float) $booking['total_amount']);
        $this->assertSame('paid', $booking['payment_status']);
        $this->assertSame(0, (new BookingItemModel())->where('booking_id', $bookingId)->countAllResults());
        $this->assertSame(1, (new \App\Models\TransactionModel())->where('booking_id', $bookingId)->countAllResults());
        $logs = glob(WRITEPATH . 'logs/log-*.log');
        $logged = implode('\n', array_map('file_get_contents', $logs));
        $this->assertStringContainsString('at record_request:', $logged);
        $this->assertStringContainsString('REVISION_TEST: simulated request persistence failure', $logged);
    }

    private function order(): array
    {
        $userId = (int) (new UserModel())->insert(['name' => 'Review Student', 'email' => 'review@example.com', 'password_hash' => password_hash('review password phrase', PASSWORD_DEFAULT), 'role' => 'customer', 'status' => 'active', ...self::details()], true);
        $bookingId = (int) (new BookingModel())->insert(['user_id' => $userId, 'reference_code' => 'EPIC-REVIEW', 'customer_name' => 'Review Student', 'customer_email' => 'review@example.com', 'service_name' => 'Summer Storage', 'booking_type' => 'summer_storage', 'status' => 'pending', 'payment_status' => 'unpaid', 'base_price' => '500.00', 'additional_box_quantity' => 0, 'additional_box_amount' => '30.00', 'subtotal' => '500.00', 'total_amount' => '500.00', 'discount_amount' => '0.00'], true);
        return [$userId, $bookingId];
    }
}
