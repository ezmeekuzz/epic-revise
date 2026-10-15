<?php

declare(strict_types=1);

use App\Libraries\SettingsService;
use App\Libraries\BookingAvailabilityService;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use App\Models\ItemModel;
use App\Models\ItemSizeModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AdminOperationsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public function testEveryAdminModuleRendersInsideTheSharedDashboardLayout(): void
    {
        [, $session] = $this->adminSession();
        $pages = [
            '/admin/'             => 'Current activity across bookings',
            '/admin/bookings'     => 'Booking Masterlist',
            '/admin/bookings/new' => 'Create an administrator-managed customer booking',
            '/admin/items'        => 'Item Masterlist',
            '/admin/items/new'    => 'Maintain the storage item catalog',
            '/admin/payments'     => 'Record manual or externally processed payments',
            '/admin/payments/new' => 'No live charge is initiated here',
            '/admin/transactions' => 'Immutable completed payment',
            '/admin/users'        => 'Search, create, update, deactivate, or remove',
            '/admin/users/new'    => 'Create a customer or administrator account',
            '/admin/settings'     => 'Email / SMTP settings',
            '/admin/account/verify' => 'Verify your identity',
        ];

        foreach ($pages as $url => $expectedText) {
            $response = $this->withSession($session)->get($url);
            $this->assertSame(200, $response->response()->getStatusCode(), 'Failed to render ' . $url . '.');
            $response->assertSee($expectedText);
            $response->assertSee('assets/css/admin.css');
            $response->assertSee('assets/vendor/sweetalert2/sweetalert2.all.min.js');
        }
    }

    public function testAdministratorCanManageBookingItemsAndFinancialLifecycle(): void
    {
        [$adminId, $session] = $this->adminSession();

        $itemResponse = $this->withSession($session)->post('/admin/items', [
            csrf_token() => csrf_hash(),
            'name'       => 'Standard storage bin',
            'sku'        => 'BIN-STD',
            'category'   => 'Storage',
            'description' => 'Secure labeled storage bin.',
            'unit_price' => '125.00',
            'is_active'  => '1',
        ]);
        $itemResponse->assertRedirectTo('/admin/items');
        $item = (new ItemModel())->where('sku', 'BIN-STD')->first();
        $this->assertNotNull($item);

        $bookingResponse = $this->withSession($session)->post('/admin/bookings', [
            csrf_token()       => csrf_hash(),
            'user_id'          => '',
            'customer_name'    => 'Taylor Student',
            'customer_email'   => 'taylor@example.com',
            'customer_phone'   => '336-555-0100',
            'student_id'       => 'HPU-1001',
            'service_name'     => 'Semester storage',
            'booking_type'     => 'summer_storage',
            'booking_date'     => '2026-09-20',
            'base_price'       => '0.00',
            'additional_box_quantity' => '0',
            'additional_box_amount' => '50.00',
            'is_study_abroad'  => '0',
            'is_summer_school' => '0',
            'summer_school_fee' => '0.00',
            'pickup_date'      => '2026-10-01',
            'pickup_time'      => '10:30',
            'pickup_location'  => 'Finch Hall',
            'room_number'      => '214',
            'status'           => 'confirmed',
            'pickup_status'    => 'scheduled',
            'dropoff_status'   => 'unscheduled',
            'discount_amount'  => '25.00',
            'admin_notes'      => 'Call on arrival.',
        ]);
        $booking = (new BookingModel())->where('customer_email', 'taylor@example.com')->first();
        $this->assertNotNull($booking);
        $bookingResponse->assertRedirectTo('/admin/bookings/' . $booking['id']);
        $this->assertStringStartsWith('EPIC-', $booking['reference_code']);

        $addItem = $this->withSession($session)->post('/admin/bookings/' . $booking['id'] . '/items', [
            csrf_token() => csrf_hash(),
            'item_id'    => (string) $item['id'],
            'quantity'   => '2',
        ]);
        $addItem->assertRedirectTo('/admin/bookings/' . $booking['id']);
        $this->seeInDatabase('booking_items', [
            'booking_id' => $booking['id'],
            'item_name'  => 'Standard storage bin',
            'quantity'   => 2,
            'line_total' => '250.00',
        ]);
        $booking = (new BookingModel())->find($booking['id']);
        $this->assertSame('250.00', $booking['subtotal']);
        $this->assertSame('225.00', $booking['total_amount']);

        $paymentResponse = $this->withSession($session)->post('/admin/payments', [
            csrf_token()        => csrf_hash(),
            'booking_id'        => (string) $booking['id'],
            'provider'          => 'manual',
            'method'            => 'card terminal',
            'amount'            => '225.00',
            'currency'          => 'USD',
            'status'            => 'completed',
            'external_reference' => 'TERM-1001',
            'notes'             => 'Processed at the office.',
        ]);
        $paymentResponse->assertRedirectTo('/admin/payments');
        $payment = (new PaymentModel())->where('booking_id', $booking['id'])->first();
        $this->assertNotNull($payment);
        $this->assertSame('completed', $payment['status']);
        $transaction = (new TransactionModel())->where('payment_id', $payment['id'])->where('type', 'payment')->first();
        $this->assertNotNull($transaction);
        $this->assertSame('225.00', $transaction['amount']);
        $this->assertSame('paid', (new BookingModel())->find($booking['id'])['payment_status']);

        $refund = $this->withSession($session)->post('/admin/payments/' . $payment['id'] . '/refund', [csrf_token() => csrf_hash()]);
        $refund->assertRedirectTo('/admin/payments');
        $this->seeInDatabase('payments', ['id' => $payment['id'], 'status' => 'refunded']);
        $this->seeInDatabase('transactions', ['payment_id' => $payment['id'], 'type' => 'refund', 'amount' => '-225.00']);
        $this->assertSame('unpaid', (new BookingModel())->find($booking['id'])['payment_status']);

        $detail = $this->withSession($session)->get('/admin/bookings/' . $booking['id']);
        $detail->assertStatus(200);
        $detail->assertSee($booking['reference_code']);
        $detail->assertSee('Standard storage bin');
        $detail->assertSee('TERM-1001');

        $this->assertGreaterThan(0, $adminId);
    }

    public function testCustomerBookingRequestUsesAvailabilityRulesAndOwnership(): void
    {
        $customerId = (int) (new UserModel())->insert([
            'name' => 'Portal Customer', 'email' => 'portal@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', ...\Tests\Support\CustomerFixture::details(),
        ], true);
        $session = [
            'customer_user_id' => $customerId, 'customer_user_name' => 'Portal Customer', 'customer_user_email' => 'portal@example.com',
            'customer_is_logged_in' => true,
        ];
        $pickupDate = (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->modify('+3 days')->format('Y-m-d');
        $dorm = (new DormModel())->where('name', 'YORK HALL')->first();
        $this->assertNotNull($dorm);

        $form = $this->withSession($session)->get('/account/bookings/new');
        $form->assertStatus(200);
        $form->assertSee('Process your service');
        $formBody = (string) $form->response()->getBody();
        $this->assertSame(3, substr_count($formBody, 'data-quantity-action="decrement"'));
        $this->assertSame(3, substr_count($formBody, 'data-quantity-action="increment"'));
        $this->assertStringContainsString('aria-label="Decrease selected item quantity"', $formBody);

        $created = $this->withSession($session)->post('/account/bookings', [
            'terms' => '1',
            csrf_token() => csrf_hash(), 'customer_phone' => '336-555-0199', 'student_id' => 'HPU-42',
            'service_name' => 'Summer Storage', 'pickup_date' => $pickupDate, 'pickup_time' => '09:30',
            'dorm_id' => (string) $dorm['id'], 'room_number' => '301',
        ]);
        $booking = (new BookingModel())->where('user_id', $customerId)->first();
        $this->assertNotNull($booking);
        $created->assertRedirectTo('/account/bookings/' . $booking['id'] . '/payment');
        $this->assertSame('pending', $booking['status']);
        $this->assertSame('Portal Customer', $booking['customer_name']);

        $detail = $this->withSession($session)->get('/account/bookings/' . $booking['id']);
        $detail->assertStatus(200);
        $detail->assertSee($booking['reference_code']);
        $detail->assertSee('YORK HALL');

        db_connect()->table('app_settings')->where('setting_key', 'bookings_enabled')->update(['setting_value' => '0']);
        $blocked = $this->withSession($session)->post('/account/bookings', [
            'terms' => '1',
            csrf_token() => csrf_hash(), 'customer_phone' => '336-555-0199', 'student_id' => '',
            'service_name' => 'Summer Storage', 'pickup_date' => $pickupDate, 'pickup_time' => '10:00',
            'dorm_id' => (string) $dorm['id'], 'room_number' => '301',
        ]);
        $blocked->assertSessionHas('error');
        $this->assertSame(1, (new BookingModel())->where('user_id', $customerId)->countAllResults());

        db_connect()->table('app_settings')->where('setting_key', 'bookings_enabled')->update(['setting_value' => '1']);
        db_connect()->table('app_settings')->where('setting_key', 'blackout_dates')->update(['setting_value' => $pickupDate]);
        $this->assertSame(
            'That date is unavailable. Please choose another pickup date.',
            (new BookingAvailabilityService())->validationError($pickupDate, '10:00'),
        );

        $publicSchedule = $this->get('/scheduling');
        $publicSchedule->assertStatus(200);
        $publicSchedule->assertSee('Scheduling available');

        [, $adminSession] = $this->adminSession();
        $adminPortalAttempt = $this->withSession($adminSession)->get('/account');
        $adminPortalAttempt->assertRedirectTo('/login');
    }

    public function testReferenceDormCatalogIsAvailableForCustomerBookings(): void
    {
        $customerId = (int) (new UserModel())->insert([
            'name' => 'Dorm Customer', 'email' => 'dorms@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', ...\Tests\Support\CustomerFixture::details(),
        ], true);
        $session = [
            'customer_user_id' => $customerId, 'customer_user_name' => 'Dorm Customer',
            'customer_user_email' => 'dorms@example.com', 'customer_is_logged_in' => true,
        ];

        $this->assertSame(28, db_connect()->table('dorms')->where('deleted_at', null)->countAllResults());
        $this->seeInDatabase('dorms', ['name' => 'BELK HALL', 'code' => 'BELK', 'is_active' => 1]);
        $this->seeInDatabase('dorms', ['name' => 'UNIVERSITY LANDING TINY HOMES', 'code' => 'UNIVERSITY-LANDING', 'is_active' => 1]);
        $this->seeInDatabase('dorms', ['name' => 'OTHER', 'code' => 'OTHER', 'is_active' => 1]);

        $form = $this->withSession($session)->get('/account/bookings/new');
        $form->assertStatus(200);
        $form->assertSee('BELK HALL');
        $form->assertSee('YORK HALL');
        $form->assertSee('OTHER');
    }

    public function testCustomerCanUpdateProfileAndProvidePickupDetailsLater(): void
    {
        $customerId = (int) (new UserModel())->insert([
            'name' => 'Initial Customer', 'email' => 'profile@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', ...\Tests\Support\CustomerFixture::details(),
        ], true);
        $session = [
            'customer_user_id' => $customerId, 'customer_user_name' => 'Initial Customer',
            'customer_user_email' => 'profile@example.com', 'customer_is_logged_in' => true,
        ];
        $dorm = (new DormModel())->where('name', 'YORK HALL')->first();
        $this->assertNotNull($dorm);

        $profilePage = $this->withSession($session)->get('/account/profile');
        $profilePage->assertStatus(200);
        $profilePage->assertSee('Parent or guardian');
        $profilePage->assertSee('Dorm / pickup location');

        $profileUpdate = $this->withSession($session)->post('/account/profile', [
            ...\Tests\Support\CustomerFixture::details(),
            csrf_token() => csrf_hash(),
            'name' => 'Updated Customer',
            'email' => 'updated-profile@example.com',
            'student_id' => 'HPU-9001',
            'phone_number' => '336-555-0142',
            'street_number' => '1200',
            'street_name' => 'University Parkway',
            'dorm_id' => (string) $dorm['id'],
            'dorm_room_number' => 'B-204',
            'parent_phone_number' => '336-555-0198',
            'parent_email_address' => 'parent@example.com',
        ]);
        $profileUpdate->assertRedirectTo('/account/profile');
        $this->seeInDatabase('users', [
            'id' => $customerId,
            'name' => 'Updated Customer',
            'email' => 'updated-profile@example.com',
            'student_id' => 'HPU-9001',
            'phone_number' => '336-555-0142',
            'street_number' => '1200',
            'street_name' => 'University Parkway',
            'dorm_id' => $dorm['id'],
            'dorm_room_number' => 'B-204',
            'parent_phone_number' => '336-555-0198',
            'parent_email_address' => 'parent@example.com',
        ]);

        $bookingResponse = $this->withSession($session)->post('/account/bookings', [
            'terms' => '1',
            csrf_token() => csrf_hash(),
            'booking_type' => 'summer_advantage',
            'box_quantity' => '2',
        ]);
        $booking = (new BookingModel())->where('user_id', $customerId)->first();
        $this->assertNotNull($booking);
        $bookingResponse->assertRedirectTo('/account/bookings/' . $booking['id'] . '/payment');
        $this->assertNull($booking['pickup_date']);
        $this->assertNull($booking['pickup_time']);
        $this->assertNull($booking['dorm_id']);
        $this->assertNull($booking['pickup_location']);
        $this->assertSame('336-555-0142', $booking['customer_phone']);
        $this->assertSame('HPU-9001', $booking['student_id']);

        $pickupPage = $this->withSession($session)->get('/account/bookings/' . $booking['id'] . '/pickup');
        $pickupPage->assertStatus(200);
        $pickupPage->assertSee('Add these details now or leave them blank');

        $pickupDate = (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->modify('+3 days')->format('Y-m-d');
        $pickupUpdate = $this->withSession($session)->post('/account/bookings/' . $booking['id'] . '/pickup', [
            csrf_token() => csrf_hash(),
            'customer_phone' => '336-555-0155',
            'student_id' => 'HPU-9001',
            'dorm_id' => (string) $dorm['id'],
            'room_number' => 'B-204',
            'pickup_date' => $pickupDate,
            'pickup_time' => '09:30',
        ]);
        $pickupUpdate->assertRedirectTo('/account/bookings/' . $booking['id']);
        $this->seeInDatabase('bookings', [
            'id' => $booking['id'],
            'customer_phone' => '336-555-0155',
            'student_id' => 'HPU-9001',
            'dorm_id' => $dorm['id'],
            'pickup_location' => 'YORK HALL',
            'room_number' => 'B-204',
            'pickup_date' => $pickupDate,
            'pickup_status' => 'scheduled',
        ]);
    }

    public function testCustomerServiceProcessingAppliesStorageAndAdvantagePricingRules(): void
    {
        $customerId = (int) (new UserModel())->insert([
            'name' => 'Pricing Customer', 'email' => 'pricing@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'customer', 'status' => 'active', ...\Tests\Support\CustomerFixture::details(),
        ], true);
        $session = [
            'customer_user_id' => $customerId, 'customer_user_name' => 'Pricing Customer',
            'customer_user_email' => 'pricing@example.com', 'customer_is_logged_in' => true,
        ];
        $pickupDate = (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->modify('+3 days')->format('Y-m-d');
        $db = db_connect();
        foreach ([
            'summer_storage_base_price' => '450.00',
            'summer_storage_additional_box_price' => '20.00',
            'summer_advantage_box_price' => '50.00',
            'summer_school_fee' => '75.00',
        ] as $key => $value) {
            $db->table('app_settings')->where('setting_key', $key)->update(['setting_value' => $value]);
        }

        $size = (new ItemSizeModel())->where('unit_price', '70.00')->first();
        $this->assertNotNull($size);

        $storageResponse = $this->withSession($session)->post('/account/bookings', [
            'terms' => '1',
            csrf_token() => csrf_hash(), 'booking_type' => 'summer_storage',
            'customer_phone' => '336-555-0101', 'student_id' => 'PRICE-1',
            'additional_box_quantity' => '2', 'is_summer_school' => '1', 'is_study_abroad' => '1',
            'items' => [['size_id' => (string) $size['id'], 'quantity' => '2']],
            'pickup_date' => $pickupDate, 'pickup_time' => '09:30',
            'pickup_location' => 'York Hall', 'room_number' => '101',
        ]);
        $storage = (new BookingModel())->where('user_id', $customerId)->where('booking_type', 'summer_storage')->first();
        $this->assertNotNull($storage);
        $storageResponse->assertRedirectTo('/account/bookings/' . $storage['id'] . '/payment');
        $this->assertSame('765.00', $storage['study_abroad_additional_storage_price']);
        $this->assertSame('1530.00', $storage['total_amount']);
        $this->seeInDatabase('booking_items', ['booking_id' => $storage['id'], 'quantity' => 2, 'line_total' => '140.00']);

        (new BookingModel())->update((int) $storage['id'], ['status' => 'completed']);
        $advantageResponse = $this->withSession($session)->post('/account/bookings', [
            'terms' => '1',
            csrf_token() => csrf_hash(), 'booking_type' => 'summer_advantage',
            'customer_phone' => '336-555-0101', 'student_id' => 'PRICE-2', 'box_quantity' => '3',
            'is_summer_school' => '1', 'is_study_abroad' => '1',
            'items' => [['size_id' => (string) $size['id'], 'quantity' => '5']],
            'pickup_date' => $pickupDate, 'pickup_time' => '10:00',
            'pickup_location' => 'York Hall', 'room_number' => '101',
        ]);
        $advantage = (new BookingModel())->where('user_id', $customerId)->where('booking_type', 'summer_advantage')->first();
        $this->assertNotNull($advantage);
        $advantageResponse->assertRedirectTo('/account/bookings/' . $advantage['id'] . '/payment');
        $this->assertSame('150.00', $advantage['total_amount']);
        $this->assertSame('0', (string) $advantage['is_summer_school']);
        $this->assertSame('0', (string) $advantage['is_study_abroad']);
        $this->assertSame(0, (new BookingItemModel())->where('booking_id', $advantage['id'])->countAllResults());
    }

    public function testBookingItemRemovalCannotCrossBookingBoundary(): void
    {
        [, $session] = $this->adminSession();
        $itemId = (int) (new ItemModel())->insert([
            'name' => 'Storage box', 'sku' => 'BOX-1', 'category' => 'Storage',
            'unit_price' => '10.00', 'is_active' => 1,
        ], true);
        $firstBookingId  = $this->booking('EPIC-FIRST');
        $secondBookingId = $this->booking('EPIC-SECOND');
        $lineId = (int) (new BookingItemModel())->insert([
            'booking_id' => $firstBookingId, 'item_id' => $itemId, 'item_name' => 'Storage box',
            'unit_price' => '10.00', 'quantity' => 1, 'line_total' => '10.00',
        ], true);

        try {
            $this->withSession($session)->post('/admin/bookings/' . $secondBookingId . '/items/' . $lineId . '/delete', [csrf_token() => csrf_hash()]);
            $this->fail('A booking item must not be removable through another booking.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $this->seeInDatabase('booking_items', ['id' => $lineId, 'booking_id' => $firstBookingId]);
    }

    public function testSettingsAreValidatedAndSecretsAreEncryptedAtRest(): void
    {
        [$adminId, $session] = $this->adminSession();

        $invalidDates = $this->withSession($session)->post('/admin/settings/booking', [
            csrf_token()                  => csrf_hash(),
            'bookings_enabled'            => '1',
            'booking_unavailable_message' => 'Contact support.',
            'daily_booking_capacity'      => '25',
            'minimum_advance_hours'       => '24',
            'pickup_start_time'           => '09:00',
            'pickup_end_time'             => '17:00',
            'slot_duration_minutes'       => '30',
            'blackout_dates'              => '2026-02-30',
        ]);
        $invalidDates->assertRedirect();
        $invalidDates->assertSessionHas('error');
        $this->assertSame('', (new SettingsService())->get('blackout_dates'));

        $pricingSave = $this->withSession($session)->post('/admin/settings/pricing', [
            csrf_token() => csrf_hash(),
            'summer_storage_base_price' => '525.00',
            'summer_advantage_box_price' => '65.00',
            'summer_school_fee' => '85.00',
        ]);
        $pricingSave->assertRedirect();
        $pricingSave->assertSessionHas('success');
        $this->assertSame('85.00', (new SettingsService())->get('summer_school_fee'));
        $this->assertSame('65.00', (new SettingsService())->get('summer_storage_additional_box_price'));

        $emailSave = $this->withSession($session)->post('/admin/settings/email', [
            csrf_token()          => csrf_hash(),
            'smtp_enabled'        => '1',
            'smtp_host'           => 'smtp.example.com',
            'smtp_port'           => '587',
            'smtp_username'       => 'mailer@example.com',
            'smtp_password'       => 'super-secret-password',
            'smtp_crypto'         => 'tls',
            'email_from_address'  => 'mailer@example.com',
            'email_from_name'     => 'Epic Storage',
        ]);
        $emailSave->assertRedirect();
        $emailSave->assertSessionHas('success');

        $row = db_connect()->table('app_settings')->where('setting_key', 'smtp_password')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertNotSame('super-secret-password', $row['setting_value']);
        $this->assertSame('super-secret-password', (new SettingsService())->get('smtp_password'));
        $this->assertSame((string) $adminId, (string) $row['updated_by']);

        $unsafeClear = $this->withSession($session)->post('/admin/settings/email', [
            csrf_token()          => csrf_hash(),
            'smtp_enabled'        => '1',
            'smtp_host'           => 'smtp.example.com',
            'smtp_port'           => '587',
            'smtp_username'       => 'mailer@example.com',
            'smtp_password'       => '',
            'clear_smtp_password' => '1',
            'smtp_crypto'         => 'tls',
            'email_from_address'  => 'mailer@example.com',
            'email_from_name'     => 'Epic Storage',
        ]);
        $unsafeClear->assertSessionHas('error');
        $this->assertSame('super-secret-password', (new SettingsService())->get('smtp_password'));

        $paymentPage = $this->withSession($session)->get('/admin/settings');
        $paymentPage->assertStatus(200);
        $paymentPage->assertSee('Square credentials');
        $paymentPage->assertSee('Application ID');
        $paymentPage->assertSee('Location ID');
        $paymentPage->assertSee('Access Token');
        $paymentPage->assertSee('Webhook Signature Key');

        $squareSave = $this->withSession($session)->post('/admin/settings/payment', [
            csrf_token() => csrf_hash(),
            'payments_enabled' => '1',
            'payment_provider' => 'square',
            'payment_mode' => 'sandbox',
            'payment_currency' => 'USD',
            'square_application_id' => 'sandbox-sq0idb-example',
            'square_access_token' => 'sandbox-access-token-secret',
            'square_location_id' => 'LSANDBOX123',
            'square_webhook_signature_key' => 'sandbox-webhook-signature-secret',
        ]);
        $squareSave->assertRedirectTo('/admin/settings#payment');
        $squareSave->assertSessionHas('success');
        $this->assertSame('square', (new SettingsService())->get('payment_provider'));
        $this->assertSame('LSANDBOX123', (new SettingsService())->get('square_location_id'));
        $this->assertSame('sandbox-access-token-secret', (new SettingsService())->get('square_access_token'));
        $this->assertSame('sandbox-webhook-signature-secret', (new SettingsService())->get('square_webhook_signature_key'));

        $accessTokenRow = db_connect()->table('app_settings')->where('setting_key', 'square_access_token')->get()->getRowArray();
        $webhookKeyRow = db_connect()->table('app_settings')->where('setting_key', 'square_webhook_signature_key')->get()->getRowArray();
        $this->assertNotNull($accessTokenRow);
        $this->assertNotNull($webhookKeyRow);
        $this->assertSame('1', (string) $accessTokenRow['is_encrypted']);
        $this->assertSame('1', (string) $webhookKeyRow['is_encrypted']);
        $this->assertNotSame('sandbox-access-token-secret', $accessTokenRow['setting_value']);
        $this->assertNotSame('sandbox-webhook-signature-secret', $webhookKeyRow['setting_value']);

        $unsafeSquareClear = $this->withSession($session)->post('/admin/settings/payment', [
            csrf_token() => csrf_hash(),
            'payments_enabled' => '1',
            'payment_provider' => 'square',
            'payment_mode' => 'sandbox',
            'payment_currency' => 'USD',
            'square_application_id' => 'sandbox-sq0idb-example',
            'square_access_token' => '',
            'clear_square_access_token' => '1',
            'square_location_id' => 'LSANDBOX123',
            'square_webhook_signature_key' => '',
        ]);
        $unsafeSquareClear->assertSessionHas('error');
        $this->assertSame('sandbox-access-token-secret', (new SettingsService())->get('square_access_token'));
    }

    /** @return array{int, array<string, int|string|bool>} */
    private function adminSession(): array
    {
        $id = (int) (new UserModel())->insert([
            'name' => 'Operations Admin', 'email' => uniqid('admin-', true) . '@example.com',
            'password_hash' => password_hash('valid password 123', PASSWORD_DEFAULT),
            'role' => 'admin', 'status' => 'active',
        ], true);

        return [$id, [
            'admin_user_id' => $id, 'admin_user_name' => 'Operations Admin', 'admin_user_email' => 'admin@example.com',
            'admin_is_logged_in' => true,
        ]];
    }

    private function booking(string $reference): int
    {
        return (int) (new BookingModel())->insert([
            'reference_code' => $reference, 'customer_name' => 'Test Customer',
            'customer_email' => strtolower($reference) . '@example.com', 'service_name' => 'Storage',
            'status' => 'pending', 'payment_status' => 'unpaid', 'subtotal' => '0.00',
            'discount_amount' => '0.00', 'total_amount' => '0.00',
        ], true);
    }
}
