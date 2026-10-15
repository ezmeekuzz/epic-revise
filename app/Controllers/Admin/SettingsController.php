<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\SettingsService;
use App\Libraries\SmtpService;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class SettingsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $settings = new SettingsService();

        return view('admin/settings/index', [
            'title' => 'Settings', 'activeAdminPage' => 'settings',
            'general' => $settings->displayGroup('general'), 'booking' => $settings->displayGroup('booking'), 'pricing' => $settings->displayGroup('pricing'),
            'email' => $settings->displayGroup('email'), 'payment' => $settings->displayGroup('payment'),
            'timezones' => ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'UTC'],
        ]);
    }

    public function update(string $group): RedirectResponse
    {
        $payload = $this->payload($group);
        if ($payload === null) {
            return redirect()->route('admin.settings')->with('error', 'Unknown settings section.');
        }

        if (! $this->validateData($payload['values'], $payload['rules'])) {
            return redirect()->to(site_url('admin/settings#' . $group))->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($group === 'booking' && $payload['values']['pickup_start_time'] >= $payload['values']['pickup_end_time']) {
            return redirect()->to(site_url('admin/settings#booking'))->withInput()->with('error', 'Pickup end time must be later than the start time.');
        }

        if ($group === 'booking'
            && strtotime($payload['values']['pickup_end_time']) - strtotime($payload['values']['pickup_start_time']) < ((int) $payload['values']['slot_duration_minutes'] * 60)) {
            return redirect()->to(site_url('admin/settings#booking'))->withInput()->with('error', 'The pickup window must contain at least one complete booking slot.');
        }

        if ($group === 'booking' && ! $this->blackoutDatesAreValid($payload['values']['blackout_dates'])) {
            return redirect()->to(site_url('admin/settings#booking'))->withInput()->with('error', 'Every blackout date must use the YYYY-MM-DD format and be a real calendar date.');
        }

        if ($group === 'pricing' && (float) $payload['values']['summer_storage_base_price'] < 0) {
            return redirect()->to(site_url('admin/settings#pricing'))->withInput()->with('error', 'Summer Storage base price cannot be negative.');
        }

        if ($group === 'email' && $payload['values']['smtp_enabled'] === '1') {
            $current = (new SettingsService())->displayGroup('email');
            $clearingPassword = in_array('smtp_password', $payload['clearSecrets'], true);
            $hasPassword = ! $clearingPassword && (($current['smtp_password_configured'] ?? false) || $payload['values']['smtp_password'] !== '');
            if ($payload['values']['smtp_host'] === '' || $payload['values']['smtp_username'] === '' || ! $hasPassword) {
                return redirect()->to(site_url('admin/settings#email'))->withInput()->with('error', 'SMTP host, username, and password are required before enabling SMTP.');
            }
        }

        if ($group === 'payment' && $payload['values']['payments_enabled'] === '1' && $payload['values']['payment_provider'] === 'square') {
            $current = (new SettingsService())->displayGroup('payment');
            $clearingAccessToken = in_array('square_access_token', $payload['clearSecrets'], true);
            $hasAccessToken = ! $clearingAccessToken
                && (($current['square_access_token_configured'] ?? false) || $payload['values']['square_access_token'] !== '');

            if ($payload['values']['square_application_id'] === '' || $payload['values']['square_location_id'] === '' || ! $hasAccessToken) {
                return redirect()->to(site_url('admin/settings#payment'))->withInput()->with(
                    'error',
                    'Square Application ID, Location ID, and Access Token are required before enabling customer payments.',
                );
            }
        }

        try {
            (new SettingsService())->saveGroup($group, $payload['values'], $payload['clearSecrets'], (int) session()->get('admin_user_id'));
        } catch (Throwable $exception) {
            log_message('error', 'Unable to save {group} settings: {type}', ['group' => $group, 'type' => $exception::class]);
            return redirect()->to(site_url('admin/settings#' . $group))->with('error', 'The settings could not be saved. Check that the application encryption key is configured.');
        }

        return redirect()->to(site_url('admin/settings#' . $group))->with('success', ucfirst($group) . ' settings updated.');
    }

    public function testEmail(): RedirectResponse
    {
        $recipient = mb_strtolower(trim((string) $this->request->getPost('recipient')));
        if (! $this->validateData(['recipient' => $recipient], ['recipient' => 'required|valid_email|max_length[254]'])) {
            return redirect()->to(site_url('admin/settings#email'))->with('errors', $this->validator->getErrors());
        }

        $key = 'smtp_test_' . hash('sha256', (string) session()->get('admin_user_id') . '|' . $this->request->getIPAddress());
        if (! service('throttler')->check($key, 5, MINUTE)) {
            return redirect()->to(site_url('admin/settings#email'))->with('error', 'Too many test attempts. Please wait one minute.');
        }

        try {
            if (! (new SmtpService())->sendTest($recipient)) {
                throw new RuntimeException('The SMTP server did not accept the test message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'SMTP test failed: {type}', ['type' => $exception::class]);
            return redirect()->to(site_url('admin/settings#email'))->with('error', 'SMTP test failed. Verify the saved host, port, encryption, username, and password.');
        }

        return redirect()->to(site_url('admin/settings#email'))->with('success', 'The SMTP server accepted the test message. Confirm arrival in the recipient inbox or spam folder.');
    }

    /** @return array{values: array<string, string>, rules: array<string, string>, clearSecrets: list<string>}|null */
    private function payload(string $group): ?array
    {
        if ($group === 'general') {
            return ['values' => [
                'homepage_message_title' => trim((string) $this->request->getPost('homepage_message_title')),
                'homepage_message_body' => trim((string) $this->request->getPost('homepage_message_body')),
                'current_terms_text' => trim((string) $this->request->getPost('current_terms_text')),
                'business_name' => trim((string) $this->request->getPost('business_name')),
                'support_email' => mb_strtolower(trim((string) $this->request->getPost('support_email'))),
                'business_phone' => trim((string) $this->request->getPost('business_phone')),
                'business_timezone' => (string) $this->request->getPost('business_timezone'),
            ], 'rules' => [
                'homepage_message_title' => 'required|max_length[200]',
                'homepage_message_body' => 'required|max_length[3000]',
                'current_terms_text' => 'permit_empty|max_length[250000]',
                'business_name' => 'required|max_length[120]', 'support_email' => 'required|valid_email|max_length[254]',
                'business_phone' => 'required|max_length[30]', 'business_timezone' => 'required|in_list[America/New_York,America/Chicago,America/Denver,America/Los_Angeles,UTC]',
            ], 'clearSecrets' => []];
        }

        if ($group === 'booking') {
            $blackoutDates = preg_replace('/\s+/', ',', trim((string) $this->request->getPost('blackout_dates'))) ?? '';
            $blackoutDates = implode(',', array_filter(array_map('trim', explode(',', $blackoutDates))));
            return ['values' => [
                'bookings_enabled' => $this->request->getPost('bookings_enabled') === '1' ? '1' : '0',
                'booking_unavailable_message' => trim((string) $this->request->getPost('booking_unavailable_message')),
                'daily_booking_capacity' => (string) $this->request->getPost('daily_booking_capacity'),
                'minimum_advance_hours' => (string) $this->request->getPost('minimum_advance_hours'),
                'pickup_start_time' => (string) $this->request->getPost('pickup_start_time'), 'pickup_end_time' => (string) $this->request->getPost('pickup_end_time'),
                'slot_duration_minutes' => (string) $this->request->getPost('slot_duration_minutes'), 'blackout_dates' => $blackoutDates,
            ], 'rules' => [
                'bookings_enabled' => 'required|in_list[0,1]', 'booking_unavailable_message' => 'required|max_length[500]',
                'daily_booking_capacity' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[1000]',
                'minimum_advance_hours' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[720]',
                'pickup_start_time' => 'required|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]', 'pickup_end_time' => 'required|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
                'slot_duration_minutes' => 'required|integer|in_list[15,30,45,60,90,120]', 'blackout_dates' => 'permit_empty|max_length[3000]',
            ], 'clearSecrets' => []];
        }

        if ($group === 'pricing') {
            $sharedBoxPrice = trim((string) $this->request->getPost('summer_advantage_box_price'));

            return ['values' => [
                'summer_storage_base_price' => trim((string) $this->request->getPost('summer_storage_base_price')),
                // Keep the legacy setting synchronized for older integrations.
                'summer_storage_additional_box_price' => $sharedBoxPrice,
                'summer_advantage_box_price' => $sharedBoxPrice,
                'summer_school_fee' => trim((string) $this->request->getPost('summer_school_fee')),
            ], 'rules' => [
                'summer_storage_base_price' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100000]',
                'summer_advantage_box_price' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100000]',
                'summer_school_fee' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100000]',
            ], 'clearSecrets' => []];
        }

        if ($group === 'email') {
            return ['values' => [
                'smtp_enabled' => $this->request->getPost('smtp_enabled') === '1' ? '1' : '0', 'smtp_host' => trim((string) $this->request->getPost('smtp_host')),
                'smtp_port' => (string) $this->request->getPost('smtp_port'), 'smtp_username' => trim((string) $this->request->getPost('smtp_username')),
                'smtp_password' => (string) $this->request->getPost('smtp_password'), 'smtp_crypto' => (string) $this->request->getPost('smtp_crypto'),
                'email_from_address' => mb_strtolower(trim((string) $this->request->getPost('email_from_address'))), 'email_from_name' => trim((string) $this->request->getPost('email_from_name')),
            ], 'rules' => [
                'smtp_enabled' => 'required|in_list[0,1]', 'smtp_host' => 'permit_empty|max_length[253]', 'smtp_port' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[65535]',
                'smtp_username' => 'permit_empty|max_length[254]', 'smtp_password' => 'permit_empty|max_length[500]', 'smtp_crypto' => 'permit_empty|in_list[tls,ssl]',
                'email_from_address' => 'required|valid_email|max_length[254]', 'email_from_name' => 'required|max_length[120]',
            ], 'clearSecrets' => $this->request->getPost('clear_smtp_password') === '1' ? ['smtp_password'] : []];
        }

        if ($group === 'payment') {
            return ['values' => [
                'payments_enabled' => $this->request->getPost('payments_enabled') === '1' ? '1' : '0', 'payment_provider' => (string) $this->request->getPost('payment_provider'),
                'payment_mode' => (string) $this->request->getPost('payment_mode'), 'payment_currency' => strtoupper(trim((string) $this->request->getPost('payment_currency'))),
                'payment_public_key' => trim((string) $this->request->getPost('payment_public_key')), 'payment_secret_key' => (string) $this->request->getPost('payment_secret_key'),
                'payment_webhook_secret' => (string) $this->request->getPost('payment_webhook_secret'),
                'square_application_id' => trim((string) $this->request->getPost('square_application_id')),
                'square_access_token' => (string) $this->request->getPost('square_access_token'),
                'square_location_id' => trim((string) $this->request->getPost('square_location_id')),
                'square_webhook_signature_key' => (string) $this->request->getPost('square_webhook_signature_key'),
            ], 'rules' => [
                'payments_enabled' => 'required|in_list[0,1]', 'payment_provider' => 'required|in_list[manual,stripe,square,paypal]',
                'payment_mode' => 'required|in_list[sandbox,live]', 'payment_currency' => 'required|alpha|exact_length[3]',
                'payment_public_key' => 'permit_empty|max_length[500]', 'payment_secret_key' => 'permit_empty|max_length[500]', 'payment_webhook_secret' => 'permit_empty|max_length[500]',
                'square_application_id' => 'permit_empty|max_length[255]', 'square_access_token' => 'permit_empty|max_length[500]',
                'square_location_id' => 'permit_empty|max_length[255]', 'square_webhook_signature_key' => 'permit_empty|max_length[500]',
            ], 'clearSecrets' => array_values(array_filter([
                $this->request->getPost('clear_payment_secret_key') === '1' ? 'payment_secret_key' : null,
                $this->request->getPost('clear_payment_webhook_secret') === '1' ? 'payment_webhook_secret' : null,
                $this->request->getPost('clear_square_access_token') === '1' ? 'square_access_token' : null,
                $this->request->getPost('clear_square_webhook_signature_key') === '1' ? 'square_webhook_signature_key' : null,
            ]))];
        }

        return null;
    }

    private function blackoutDatesAreValid(string $dates): bool
    {
        if ($dates === '') {
            return true;
        }

        foreach (explode(',', $dates) as $date) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                return false;
            }
        }

        return true;
    }
}
