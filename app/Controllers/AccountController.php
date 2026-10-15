<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BookingAvailabilityService;
use App\Libraries\BookingPricingService;
use App\Libraries\BookingService;
use App\Libraries\AuthService;
use App\Libraries\SettingsService;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use App\Libraries\CustomerDetails;
use App\Libraries\CustomerOrderService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AccountController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $user = $this->currentCustomer();
        $profileDorm = ! empty($user['dorm_id']) ? (new DormModel())->find((int) $user['dorm_id']) : null;

        return view('account/dashboard', [
            ...$this->pageData('My Account | Epic Storage Solutions', 'Manage your Epic Storage Solutions customer account.', 'account'),
            'user'     => $user,
            'profileDorm' => $profileDorm,
            'bookings' => (new BookingModel())->where('user_id', (int) session()->get('customer_user_id'))->orderBy('created_at', 'DESC')->findAll(),
            'bookingOpen' => (new SettingsService())->get('bookings_enabled', '0') === '1',
        ]);
    }

    public function editProfile(): string
    {
        return view('account/profile', [
            ...$this->pageData('Personal Details | Epic Storage Solutions', 'Update your Epic customer profile.', 'account'),
            'user' => $this->currentCustomer(),
            'dorms' => $this->activeDorms(),
        ]);
    }

    public function updateProfile(): RedirectResponse
    {
        $user = $this->currentCustomer();
        $dormId = (int) $this->request->getPost('dorm_id');
        $data = [
            'name' => trim((string) $this->request->getPost('name')),
            'email' => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'student_id' => trim((string) $this->request->getPost('student_id')) ?: null,
            'phone_number' => trim((string) $this->request->getPost('phone_number')) ?: null,
            'street_number' => $this->request->getPost('street_number') === null ? ($user['street_number'] ?? null) : trim((string) $this->request->getPost('street_number')),
            'street_name' => $this->request->getPost('street_name') === null ? ($user['street_name'] ?? null) : trim((string) $this->request->getPost('street_name')),
            'dorm_id' => $dormId > 0 ? $dormId : null,
            'dorm_room_number' => trim((string) $this->request->getPost('dorm_room_number')) ?: null,
            'parent_phone_number' => trim((string) $this->request->getPost('parent_phone_number')) ?: null,
            'parent_email_address' => mb_strtolower(trim((string) $this->request->getPost('parent_email_address'))) ?: null,
        ];
        $data = [...$data, ...CustomerDetails::input($this->request)];
        $rules = [
            'name' => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|max_length[254]|is_unique[users.email,id,' . $user['id'] . ']',
            'student_id' => 'permit_empty|max_length[50]', 'phone_number' => 'permit_empty|max_length[30]',
            'street_number' => 'permit_empty|max_length[30]', 'street_name' => 'permit_empty|max_length[160]',
            'dorm_id' => 'permit_empty|is_natural_no_zero', 'dorm_room_number' => 'permit_empty|max_length[30]',
            'parent_phone_number' => 'permit_empty|max_length[30]', 'parent_email_address' => 'permit_empty|valid_email|max_length[254]',
        ];
        $rules = [...$rules, ...CustomerDetails::rules()];
        if (! $this->validateData($data, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($data['dorm_id'] !== null && $this->findActiveDorm((int) $data['dorm_id']) === null) {
            return redirect()->back()->withInput()->with('errors', ['dorm_id' => 'Choose an active dorm or leave the dorm blank.']);
        }

        try {
            if (! (new UserModel())->update((int) $user['id'], $data)) {
                throw new RuntimeException('Customer profile update failed.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Customer profile update failed: {type}', ['type' => $exception::class]);

            return redirect()->back()->withInput()->with('error', 'Your personal details could not be saved. Please review the values.');
        }

        $updated = (new UserModel())->find((int) $user['id']);
        if ($updated !== null) {
            (new AuthService())->startSession($updated);
        }

        return redirect()->route('account.profile')->with('success', 'Your personal details were updated.');
    }

    public function newBooking(): string|RedirectResponse
    {
        $active = (new CustomerOrderService())->activeOrder((int) session()->get('customer_user_id'));
        if ($active !== null) {
            return redirect()->to(site_url('account/bookings/' . $active['id'] . '/items'))->with('success', 'You already have an open order. Add additional items here.');
        }
        if (! CustomerDetails::complete($this->currentCustomer())) {
            return redirect()->route('account.profile')->with('error', 'Complete your student, parent, and shipping details before ordering.');
        }
        $settings = new SettingsService();
        $pricingService = new BookingPricingService();
        $timezone = $settings->get('business_timezone', 'America/New_York');
        $selectedBookingType = (string) $this->request->getGet('booking_type');
        if (! in_array($selectedBookingType, ['summer_storage', 'summer_advantage'], true)) {
            $selectedBookingType = 'summer_storage';
        }

        return view('account/booking_form', [
            ...$this->pageData('Request a Pickup | Epic Storage Solutions', 'Request an Epic Storage pickup window.', 'account'),
            'user' => (new UserModel())->find((int) session()->get('customer_user_id')),
            'booking' => $settings->displayGroup('booking'),
            'pricing' => $pricingService->displayPrices(),
            'itemCatalog' => $pricingService->catalog(),
            'timezone' => $timezone,
            'minimumDate' => (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d'),
            'dorms' => (new DormModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'selectedBookingType' => $selectedBookingType,
        ]);
    }

    public function createBooking(): RedirectResponse
    {
        $bookingType = (string) $this->request->getPost('booking_type');
        if (! in_array($bookingType, ['summer_storage', 'summer_advantage'], true)) {
            $legacyService = strtolower((string) $this->request->getPost('service_name'));
            $bookingType = str_contains($legacyService, 'advantage') ? 'summer_advantage' : 'summer_storage';
        }
        $user = (new UserModel())->find((int) session()->get('customer_user_id'));
        if ($user === null) {
            return redirect()->route('login')->with('error', 'Please log in again to request a booking.');
        }
        if (! CustomerDetails::complete($user)) {
            return redirect()->route('account.profile')->with('error', 'Complete your student, parent, and shipping details before ordering.');
        }
        if ($this->request->getPost('terms') !== '1') {
            return redirect()->back()->withInput()->with('error', 'Please agree to the Terms and Conditions before ordering.');
        }
        $postedPhone = trim((string) $this->request->getPost('customer_phone'));
        $postedStudentId = trim((string) $this->request->getPost('student_id'));
        $data = [
            'customer_phone' => $postedPhone !== '' ? $postedPhone : (string) ($user['phone_number'] ?? ''),
            'student_id' => $postedStudentId !== '' ? $postedStudentId : (string) ($user['student_id'] ?? ''),
            'booking_type' => $bookingType,
            'box_quantity' => max(0, (int) $this->request->getPost($bookingType === 'summer_advantage' ? 'box_quantity' : 'additional_box_quantity')),
            'is_study_abroad' => $this->request->getPost('is_study_abroad') === '1' ? '1' : '0',
            'is_summer_school' => $this->request->getPost('is_summer_school') === '1' ? '1' : '0',
            'dorm_id' => (int) $this->request->getPost('dorm_id') ?: null,
            'pickup_date' => trim((string) $this->request->getPost('pickup_date')),
            'pickup_time' => trim((string) $this->request->getPost('pickup_time')),
            'room_number' => trim((string) $this->request->getPost('room_number')),
        ];
        $rules = [
            'customer_phone' => 'required|max_length[30]', 'student_id' => 'required|max_length[50]',
            'booking_type' => 'required|in_list[summer_storage,summer_advantage]',
            'dorm_id' => 'permit_empty|is_natural_no_zero',
            'pickup_date' => 'permit_empty|valid_date[Y-m-d]', 'pickup_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
            'room_number' => 'permit_empty|max_length[30]',
        ];
        if ($bookingType === 'summer_advantage') {
            $rules['box_quantity'] = 'required|is_natural_no_zero|less_than_equal_to[100]';
        } else {
            $rules['box_quantity'] = 'required|is_natural|less_than_equal_to[100]';
            $rules['is_study_abroad'] = 'required|in_list[0,1]';
            $rules['is_summer_school'] = 'required|in_list[0,1]';
        }
        if (! $this->validateData($data, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $availabilityError = (new BookingAvailabilityService())->validationError($data['pickup_date'], $data['pickup_time']);
        if ($availabilityError !== null) {
            return redirect()->back()->withInput()->with('error', $availabilityError);
        }

        $dorm = $data['dorm_id'] !== null ? $this->findActiveDorm((int) $data['dorm_id']) : null;
        if ($data['dorm_id'] !== null && $dorm === null) {
            return redirect()->back()->withInput()->with('error', 'Please choose an active dorm location.');
        }
        $data['pickup_location'] = $dorm['name'] ?? null;

        $requestedItems = $this->request->getPost('items');
        if (! is_array($requestedItems)) {
            $requestedItems = [];
        }

        try {
            $quote = (new BookingPricingService())->quote(
                $bookingType,
                (int) $data['box_quantity'],
                $data['is_summer_school'] === '1',
                $data['is_study_abroad'] === '1',
                $requestedItems,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        unset($data['box_quantity'], $data['is_study_abroad'], $data['is_summer_school']);
        $data['pickup_date'] = $data['pickup_date'] ?: null;
        $data['pickup_time'] = $data['pickup_time'] ?: null;
        $data['room_number'] = $data['room_number'] ?: null;
        $data['service_name'] = $data['booking_type'] === 'summer_advantage' ? 'Summer Advantage Program' : 'Summer Storage';
        $db = db_connect();
        $db->transBegin();

        try {
            $orders = new CustomerOrderService();
            $orders->lockCustomer((int) $user['id']);
            $active = $orders->activeOrder((int) $user['id']);
            if ($active !== null) {
                $db->transRollback();
                return redirect()->to(site_url('account/bookings/' . $active['id'] . '/items'))->with('success', 'Your existing order is ready. Add additional items here.');
            }
            $bookingId = (int) (new BookingModel())->insert([
                ...$data, 'terms_snapshot' => (new SettingsService())->get('current_terms_text', '') ?: strip_tags(view('partials/legacy_terms')), 'terms_accepted_at' => date('Y-m-d H:i:s'), 'reference_code' => (new BookingService())->reference(), 'ordernumber' => (new BookingService())->orderNumber(), 'booking_date' => date('Y-m-d'), 'user_id' => $user['id'],
                'customer_name' => $user['name'], 'customer_email' => $user['email'], 'status' => 'pending',
                'base_price' => $quote['base_price'], 'additional_box_quantity' => $quote['box_quantity'],
                'additional_box_amount' => $quote['box_price'], 'addtl_box_total_amount' => $quote['box_total'],
                'is_study_abroad' => $quote['is_study_abroad'] ? 1 : 0,
                'study_abroad_additional_storage_price' => $quote['study_abroad_surcharge'],
                'is_summer_school' => $quote['is_summer_school'] ? 1 : 0,
                'summer_school_fee' => $quote['summer_school_fee'],
                'payment_status' => 'unpaid', 'subtotal' => $quote['subtotal'], 'discount_amount' => '0.00', 'total_amount' => $quote['total'],
            ], true);
            if ($bookingId < 1) {
                throw new RuntimeException('The booking could not be saved.');
            }

            $now = date('Y-m-d H:i:s');
            foreach ($quote['items'] as $line) {
                $inserted = (new BookingItemModel())->insert([
                    ...$line,
                    'booking_id' => $bookingId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                if ($inserted === false) {
                    throw new RuntimeException('A selected item could not be saved.');
                }
            }

            if (! $db->transStatus()) {
                throw new RuntimeException('The booking transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Customer booking creation failed: {type}', ['type' => $exception::class]);

            return redirect()->back()->withInput()->with('error', 'Your booking request could not be saved. Please try again.');
        }

        return redirect()->route('account.bookings.payment', [$bookingId])->with('success', 'Your service request was saved. Continue with secure payment.');
    }

    public function additionalItems(int $id): string|RedirectResponse
    {
        $booking = $this->ownedBooking($id);
        if (in_array($booking['status'], ['completed', 'cancelled'], true)) {
            return redirect()->route('account.bookings.show', [$id])->with('error', 'This order is closed. Contact EPIC for assistance.');
        }
        $token = bin2hex(random_bytes(32));
        session()->set('order_addition_' . $id, $token);
        return view('account/add_items', [
            ...$this->pageData('Add Additional Items | Epic Storage Solutions', 'Add items to your existing order.', 'account'),
            'booking' => $booking, 'requestToken' => $token,
            'itemCatalog' => (new BookingPricingService())->catalog(),
        ]);
    }

    public function saveAdditionalItems(int $id): RedirectResponse
    {
        $this->ownedBooking($id);
        $token = (string) $this->request->getPost('request_token');
        $expected = (string) session()->get('order_addition_' . $id);
        if ($expected === '' || ! hash_equals($expected, $token)) {
            return redirect()->to(site_url('account/bookings/' . $id . '/items'))->with('error', 'This form has expired. Please review and submit again.');
        }
        $boxes = $this->request->getPost('additional_boxes');
        $quantities = $this->request->getPost('quantities') ?? [];
        if (! is_scalar($boxes) || ! preg_match('/^\d{1,3}$/', (string) $boxes) || ! is_array($quantities) || count($quantities) > 500) {
            return redirect()->back()->withInput()->with('error', 'Enter valid whole-number quantities.');
        }
        $items = [];
        foreach ($quantities as $size => $quantity) {
            if (! is_scalar($quantity) || ! preg_match('/^\d{1,3}$/', (string) $quantity) || ! ctype_digit((string) $size)) {
                return redirect()->back()->withInput()->with('error', 'Enter valid whole-number item quantities.');
            }
            if ((int) $quantity > 0) {
                $items[] = ['size_id' => (int) $size, 'quantity' => (int) $quantity];
            }
        }
        try {
            (new CustomerOrderService())->add((int) session()->get('customer_user_id'), $id, (int) $boxes, $items, $token);
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Order additions failed: {type}', ['type' => $exception::class]);
            return redirect()->back()->withInput()->with('error', 'Your items could not be saved. Please try again.');
        }
        return redirect()->route('account.bookings.show', [$id])->with('success', 'Additional items saved to your existing order. Review the updated balance and continue to payment.');
    }

    public function showBooking(int $id): string
    {
        $booking = $this->ownedBooking($id);

        return view('account/booking_show', [
            ...$this->pageData('Booking ' . $booking['reference_code'] . ' | Epic Storage Solutions', 'View your Epic Storage booking details.', 'account'),
            'booking' => $booking,
            'dorm' => $booking['dorm_id'] ? (new DormModel())->find((int) $booking['dorm_id']) : null,
            'items' => (new BookingItemModel())->where('booking_id', $id)->findAll(),
            'payments' => (new PaymentModel())->where('booking_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'transactions' => (new TransactionModel())->where('booking_id', $id)->where('status', 'completed')->findAll(),
        ]);
    }

    public function myBookings(): string
    {
        return view('account/bookings_list', [
            ...$this->pageData('My Bookings | Epic Storage Solutions', 'View every Epic Storage booking you have made.', 'account'),
            'bookings' => $this->bookingsWithFinancials(),
            'bookingOpen' => (new SettingsService())->get('bookings_enabled', '0') === '1',
        ]);
    }

    public function payments(): string
    {
        return view('account/payments_list', [
            ...$this->pageData('Online Payments | Epic Storage Solutions', 'Pay securely for any Epic Storage booking balance.', 'account'),
            'bookings' => $this->bookingsWithFinancials(),
        ]);
    }

    public function pickupDates(): string
    {
        return view('account/pickup_dates', [
            ...$this->pageData('Date Leaving Campus | Epic Storage Solutions', 'Review and manage the pickup date for every booking.', 'account'),
            'bookings' => $this->bookingsWithFinancials(),
            'booking' => (new SettingsService())->displayGroup('booking'),
        ]);
    }

    public function dropoffDates(): string
    {
        return view('account/dropoff_dates', [
            ...$this->pageData('Manage Drop-off Dates | Epic Storage Solutions', 'Review and manage the drop-off date for every booking.', 'account'),
            'bookings' => $this->bookingsWithFinancials(),
            'booking' => (new SettingsService())->displayGroup('booking'),
        ]);
    }

    public function paymentHistory(): string
    {
        return view('account/payment_history', [
            ...$this->pageData('Payment History Logs | Epic Storage Solutions', 'Every payment made toward your Epic Storage bookings.', 'account'),
            'payments' => (new PaymentModel())
                ->select('payments.*, bookings.reference_code AS booking_reference, bookings.service_name')
                ->join('bookings', 'bookings.id = payments.booking_id')
                ->where('payments.user_id', (int) session()->get('customer_user_id'))
                ->orderBy('payments.created_at', 'DESC')
                ->findAll(),
        ]);
    }

    public function balance(): string
    {
        $bookings = $this->bookingsWithFinancials();

        return view('account/balance', [
            ...$this->pageData('Balance Management | Epic Storage Solutions', 'Track and pay the outstanding balance on every booking.', 'account'),
            'bookings' => $bookings,
            'totalBalance' => array_sum(array_column($bookings, 'balance')),
        ]);
    }

    public function editPickup(int $id): string
    {
        return view('account/pickup_form', [
            ...$this->pageData('Pickup Details | Epic Storage Solutions', 'Add or update the pickup details for your booking.', 'account'),
            'booking' => $this->ownedBooking($id),
            'user' => $this->currentCustomer(),
            'dorms' => $this->activeDorms(),
            'minimumDate' => $this->minimumPickupDate(),
        ]);
    }

    public function updatePickup(int $id): RedirectResponse
    {
        $booking = $this->ownedBooking($id);
        $dormId = (int) $this->request->getPost('dorm_id');
        $dropoffDormId = (int) $this->request->getPost('dropoff_dorm_id');
        $data = [
            'customer_phone' => trim((string) $this->request->getPost('customer_phone')),
            'student_id' => trim((string) $this->request->getPost('student_id')),
            'dorm_id' => $dormId > 0 ? $dormId : null,
            'pickup_date' => trim((string) $this->request->getPost('pickup_date')),
            'pickup_time' => trim((string) $this->request->getPost('pickup_time')),
            'room_number' => trim((string) $this->request->getPost('room_number')),
            'dropoff_dorm_id' => $dropoffDormId > 0 ? $dropoffDormId : null,
            'dropoff_date' => trim((string) $this->request->getPost('dropoff_date')),
            'dropoff_time' => trim((string) $this->request->getPost('dropoff_time')),
            'dropoff_room_number' => trim((string) $this->request->getPost('dropoff_room_number')),
        ];
        if (! $this->validateData($data, [
            'customer_phone' => 'permit_empty|max_length[30]',
            'student_id' => 'permit_empty|max_length[50]',
            'dorm_id' => 'permit_empty|is_natural_no_zero',
            'pickup_date' => 'permit_empty|valid_date[Y-m-d]',
            'pickup_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
            'room_number' => 'permit_empty|max_length[30]',
            'dropoff_dorm_id' => 'permit_empty|is_natural_no_zero',
            'dropoff_date' => 'permit_empty|valid_date[Y-m-d]',
            'dropoff_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
            'dropoff_room_number' => 'permit_empty|max_length[30]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $availability = new BookingAvailabilityService();
        $availabilityError = $availability->validationError($data['pickup_date'], $data['pickup_time'], 'pickup')
            ?? $availability->validationError($data['dropoff_date'], $data['dropoff_time'], 'dropoff');
        if ($availabilityError !== null) {
            return redirect()->back()->withInput()->with('error', $availabilityError);
        }
        $dorm = $data['dorm_id'] !== null ? $this->findActiveDorm((int) $data['dorm_id']) : null;
        if ($data['dorm_id'] !== null && $dorm === null) {
            return redirect()->back()->withInput()->with('error', 'Please choose an active pickup dorm.');
        }
        $dropoffDorm = $data['dropoff_dorm_id'] !== null ? $this->findActiveDorm((int) $data['dropoff_dorm_id']) : null;
        if ($data['dropoff_dorm_id'] !== null && $dropoffDorm === null) {
            return redirect()->back()->withInput()->with('error', 'Please choose an active drop-off dorm.');
        }

        $pickupScheduled = $data['pickup_date'] !== '' && $data['pickup_time'] !== '';
        $pickupStatus = ($booking['pickup_status'] ?? '') === 'completed' ? 'completed' : ($pickupScheduled ? 'scheduled' : 'unscheduled');
        $dropoffScheduled = $data['dropoff_date'] !== '' && $data['dropoff_time'] !== '';
        $dropoffStatus = ($booking['dropoff_status'] ?? '') === 'completed' ? 'completed' : ($dropoffScheduled ? 'scheduled' : 'unscheduled');
        try {
            if (! (new BookingModel())->update($id, [
                'customer_phone' => $data['customer_phone'] ?: null,
                'student_id' => $data['student_id'] ?: null,
                'dorm_id' => $data['dorm_id'],
                'pickup_date' => $data['pickup_date'] ?: null,
                'pickup_time' => $data['pickup_time'] ?: null,
                'pickup_location' => $dorm['name'] ?? null,
                'room_number' => $data['room_number'] ?: null,
                'pickup_status' => $pickupStatus,
                'dropoff_date' => $data['dropoff_date'] ?: null,
                'dropoff_time' => $data['dropoff_time'] ?: null,
                'dropoff_location' => $dropoffDorm['name'] ?? null,
                'dropoff_room_number' => $data['dropoff_room_number'] ?: null,
                'dropoff_status' => $dropoffStatus,
            ])) {
                throw new RuntimeException('Pickup details update failed.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Customer pickup update failed: {type}', ['type' => $exception::class]);

            return redirect()->back()->withInput()->with('error', 'Pickup details could not be saved. Please try again.');
        }

        return redirect()->route('account.bookings.show', [$id])->with('success', 'Pickup and drop-off details updated.');
    }

    /** @return array<string, mixed> */
    private function currentCustomer(): array
    {
        $user = (new UserModel())->where('id', (int) session()->get('customer_user_id'))->where('role', 'customer')->first();
        if ($user === null) {
            throw PageNotFoundException::forPageNotFound('Customer account not found.');
        }

        return $user;
    }

    /** @return list<array<string, mixed>> */
    private function bookingsWithFinancials(): array
    {
        $bookings = (new BookingModel())
            ->where('user_id', (int) session()->get('customer_user_id'))
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $transactions = new TransactionModel();
        foreach ($bookings as &$booking) {
            $row  = $transactions->selectSum('amount')->where('booking_id', (int) $booking['id'])->where('status', 'completed')->first();
            $paid = round((float) ($row['amount'] ?? 0), 2);

            $booking['paid']    = $paid;
            $booking['balance'] = max(0, round((float) $booking['total_amount'] - $paid, 2));
        }

        return $bookings;
    }

    /** @return array<string, mixed> */
    private function ownedBooking(int $id): array
    {
        $booking = (new BookingModel())->where('id', $id)->where('user_id', (int) session()->get('customer_user_id'))->first();
        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound('Booking not found.');
        }

        return $booking;
    }

    /** @return list<array<string, mixed>> */
    private function activeDorms(): array
    {
        return (new DormModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }

    /** @return array<string, mixed>|null */
    private function findActiveDorm(int $id): ?array
    {
        return (new DormModel())->where('id', $id)->where('is_active', 1)->first();
    }

    private function minimumPickupDate(): string
    {
        $settings = new SettingsService();
        $timezone = $settings->get('business_timezone', 'America/New_York');

        return (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d');
    }

    /** @return array<string, string|null> */
    private function pageData(string $title, string $description, string $activePage): array
    {
        return [
            'title'        => $title,
            'description'  => $description,
            'activePage'   => $activePage,
            'canonicalUrl' => current_url(),
            'socialImage'  => null,
            'robots'       => 'noindex,nofollow',
        ];
    }
}
