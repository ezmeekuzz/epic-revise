<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Libraries\BookingAvailabilityService;
use App\Libraries\BookingCheckout;
use App\Libraries\BookingPricingService;
use App\Libraries\SettingsService;
use App\Models\DormModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

/**
 * Validates and saves a booking form submission. Shared by the account booking form
 * (logged in, $user given) and the public booking form (no account, $user null).
 */
trait SubmitsBookings
{
    /**
     * View data for app/Views/account/booking_form.php in either mode.
     *
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    protected function bookingFormData(?array $user): array
    {
        $settings = new SettingsService();
        $pricingService = new BookingPricingService();
        $timezone = $settings->get('business_timezone', 'America/New_York');
        $selectedBookingType = (string) $this->request->getGet('booking_type');
        $lockedService = in_array($selectedBookingType, ['summer_storage', 'summer_advantage'], true);
        if (! $lockedService) {
            $selectedBookingType = 'summer_storage';
        }

        return [
            'lockedService' => $lockedService,
            'user' => $user ?? [],
            'guest' => $user === null,
            'layout' => $user === null ? 'layouts/site' : 'layouts/account',
            'formAction' => site_url($user === null ? 'book' : 'account/bookings'),
            'availabilityUrl' => site_url('book/availability'),
            'booking' => $settings->displayGroup('booking'),
            'pricing' => $pricingService->displayPrices(),
            'itemCatalog' => $pricingService->catalog(),
            'timezone' => $timezone,
            'minimumDate' => (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d'),
            'dorms' => (new DormModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'selectedBookingType' => $selectedBookingType,
        ];
    }

    /**
     * @param array<string, mixed>|null $user
     * @return RedirectResponse|array{status: 'created'|'existing', id: int}
     */
    protected function submitBooking(?array $user): RedirectResponse|array
    {
        $guest = $user === null;
        $post = fn (string $key): string => trim((string) $this->request->getPost($key));

        $bookingType = $post('booking_type');
        if (! in_array($bookingType, ['summer_storage', 'summer_advantage'], true)) {
            $bookingType = str_contains(strtolower($post('service_name')), 'advantage') ? 'summer_advantage' : 'summer_storage';
        }
        if ($this->request->getPost('terms') !== '1') {
            return redirect()->back()->withInput()->with('errors', ['terms' => 'Please agree to the Terms and Conditions before ordering.']);
        }

        $data = [
            'customer_name' => $guest ? $post('customer_name') : (string) $user['name'],
            'customer_email' => $guest ? mb_strtolower($post('customer_email')) : (string) $user['email'],
            'customer_phone' => $post('customer_phone') ?: (string) ($user['phone_number'] ?? ''),
            'student_id' => $post('student_id') ?: (string) ($user['student_id'] ?? ''),
            'parent_phone_number' => $post('parent_phone_number') ?: (string) ($user['parent_phone_number'] ?? ''),
            'parent_email_address' => mb_strtolower($post('parent_email_address')) ?: (string) ($user['parent_email_address'] ?? ''),
            'booking_type' => $bookingType,
            'box_quantity' => max(0, (int) $this->request->getPost($bookingType === 'summer_advantage' ? 'box_quantity' : 'additional_box_quantity')),
            'is_study_abroad' => $this->request->getPost('is_study_abroad') === '1' ? '1' : '0',
            'is_summer_school' => $this->request->getPost('is_summer_school') === '1' ? '1' : '0',
            'dorm_id' => (int) $this->request->getPost('dorm_id') ?: null,
            'pickup_date' => $post('pickup_date'),
            'pickup_time' => $post('pickup_time'),
            'room_number' => $post('room_number'),
        ];

        $phoneRule = 'required|min_length[7]|max_length[30]|regex_match[/^[+0-9(). \-]+$/]';
        $rules = [
            'customer_phone' => $phoneRule,
            'student_id' => 'required|max_length[50]',
            'parent_phone_number' => $phoneRule,
            'parent_email_address' => 'required|valid_email|max_length[254]',
            'booking_type' => 'required|in_list[summer_storage,summer_advantage]',
            'dorm_id' => 'permit_empty|is_natural_no_zero',
            'pickup_date' => 'permit_empty|valid_date[Y-m-d]',
            'pickup_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
            'room_number' => 'permit_empty|max_length[30]',
        ];
        if ($guest) {
            $rules['customer_name'] = 'required|min_length[2]|max_length[100]';
            $rules['customer_email'] = 'required|valid_email|max_length[254]';
        }
        if ($bookingType === 'summer_advantage') {
            $rules['box_quantity'] = 'required|is_natural_no_zero|less_than_equal_to[100]';
        } else {
            $rules['box_quantity'] = 'required|is_natural|less_than_equal_to[100]';
            $rules['is_study_abroad'] = 'required|in_list[0,1]';
            $rules['is_summer_school'] = 'required|in_list[0,1]';
        }
        if (! $this->validateData($data, $rules, [
            'customer_phone' => ['required' => 'Enter the student phone number.', 'regex_match' => 'Enter a phone number using digits, spaces, +, (, ), . or -.'],
            'parent_phone_number' => ['required' => 'Enter a parent or guardian phone number.', 'regex_match' => 'Enter a phone number using digits, spaces, +, (, ), . or -.'],
            'parent_email_address' => ['required' => 'Enter a parent or guardian email address.'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $availabilityError = (new BookingAvailabilityService())->validationError($data['pickup_date'], $data['pickup_time']);
        if ($availabilityError !== null) {
            return redirect()->back()->withInput()->with('error', $availabilityError);
        }

        $dorm = $data['dorm_id'] !== null ? (new DormModel())->where('id', (int) $data['dorm_id'])->where('is_active', 1)->first() : null;
        if ($data['dorm_id'] !== null && $dorm === null) {
            return redirect()->back()->withInput()->with('error', 'Please choose an active dorm location.');
        }

        $requestedItems = $this->request->getPost('items');
        try {
            $quote = (new BookingPricingService())->quote(
                $bookingType,
                (int) $data['box_quantity'],
                $data['is_summer_school'] === '1',
                $data['is_study_abroad'] === '1',
                is_array($requestedItems) ? $requestedItems : [],
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        unset($data['box_quantity'], $data['is_study_abroad'], $data['is_summer_school']);
        $data['pickup_location'] = $dorm['name'] ?? null;
        $data['pickup_date'] = $data['pickup_date'] ?: null;
        $data['pickup_time'] = $data['pickup_time'] ?: null;
        $data['room_number'] = $data['room_number'] ?: null;
        $data['service_name'] = $bookingType === 'summer_advantage' ? 'Summer Advantage Program' : 'Summer Storage';

        try {
            return (new BookingCheckout())->create($data, $quote, $guest ? null : (int) $user['id']);
        } catch (Throwable $exception) {
            log_message('error', 'Customer booking creation failed: {type}', ['type' => $exception::class]);

            return redirect()->back()->withInput()->with('alert', ['type' => 'error', 'title' => 'Booking not saved', 'message' => 'Your booking request could not be saved. Please try again.']);
        }
    }
}
