<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\SubmitsBookings;
use App\Libraries\AuthService;
use App\Libraries\BookingAvailabilityService;
use App\Libraries\BookingLinkService;
use App\Libraries\BookingNotifier;
use App\Libraries\SmtpService;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use App\Models\TransactionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Booking without an account. Reuses the account booking form and payment page;
 * the booking is saved like a walk-in (no user_id) until its email is proven.
 */
final class PublicBookingController extends BaseController
{
    use SubmitsBookings;

    protected $helpers = ['form', 'url'];

    public function new(): string|RedirectResponse
    {
        if ($this->isCustomerLoggedIn()) {
            $type = (string) $this->request->getGet('booking_type');

            return redirect()->to(site_url('account/bookings/new' . ($type !== '' ? '?booking_type=' . rawurlencode($type) : '')));
        }

        return view('account/booking_form', [
            ...$this->pageData('Book Storage | EPIC Storage Solutions', 'Book EPIC Storage leaving campus, storage and redelivery online.'),
            ...$this->bookingFormData(null),
        ]);
    }

    public function create(): RedirectResponse
    {
        if ($this->isCustomerLoggedIn()) {
            return redirect()->to(site_url('account/bookings/new'))->with('info', 'You are logged in, so this booking will be saved to your account.');
        }

        $ipKey = 'guest_booking_' . hash('sha256', $this->request->getIPAddress());
        if (! service('throttler')->check($ipKey, 10, HOUR)) {
            return redirect()->back()->withInput()->with('alert', ['type' => 'warning', 'title' => 'Please slow down', 'message' => 'Too many bookings were started from this connection. Please wait a little while and try again, or contact our team.']);
        }

        $result = $this->submitBooking(null);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $links = new BookingLinkService();
        $links->grantGuestAccess($result['id']);
        $this->sendBookingReceivedEmail($result['id']);
        (new BookingNotifier())->adminBookingCreated($result['id']);

        return redirect()->to(site_url('book/' . $result['id'] . '/pay'))->with('success', 'Your booking was saved. Complete your payment below.');
    }

    /** JSON list of leaving campus slots for the booking form's slot buttons. */
    public function availability(): ResponseInterface
    {
        $date = trim((string) $this->request->getGet('date'));

        return $this->response->setJSON((new BookingAvailabilityService())->slotsFor($date));
    }

    /** Emailed "Pay now" link: grants this session access to pay one booking. */
    public function payLink(string $token): RedirectResponse
    {
        $links = new BookingLinkService();
        $row = $links->findValidToken($token, BookingLinkService::PURPOSE_PAY);
        if ($row === null || (new BookingModel())->find((int) $row['booking_id']) === null) {
            return redirect()->to(site_url('contact'))->with('alert', ['type' => 'warning', 'title' => 'Payment link unavailable', 'message' => 'This payment link is not valid or has expired. Contact our team and we will send you a new one.']);
        }

        $links->grantGuestAccess((int) $row['booking_id']);

        return redirect()->to(site_url('book/' . (int) $row['booking_id'] . '/pay'));
    }

    /**
     * Emailed "Add this booking to my account" link. Only the inbox owner receives it,
     * so clicking proves the email: the booking is linked and the customer is logged in.
     */
    public function claim(string $token): RedirectResponse
    {
        $links = new BookingLinkService();
        $unavailable = ['type' => 'warning', 'title' => 'Link unavailable', 'message' => 'This link is not valid, has expired, or was already used. Log in to see the bookings in your account.'];

        $row = $links->findValidToken($token, BookingLinkService::PURPOSE_CLAIM);
        $booking = $row !== null ? (new BookingModel())->find((int) $row['booking_id']) : null;
        $user = $booking !== null ? $links->activeCustomerByEmail((string) $booking['customer_email']) : null;
        if ($row === null || $booking === null || $user === null) {
            return redirect()->route('login')->with('alert', $unavailable);
        }
        // Never move a booking that already belongs to a different account.
        if (! empty($booking['user_id']) && (int) $booking['user_id'] !== (int) $user['id']) {
            return redirect()->route('login')->with('alert', $unavailable);
        }
        if (! $links->consumeToken((int) $row['id'])) {
            return redirect()->route('login')->with('alert', $unavailable);
        }

        $links->linkUnclaimed($user);
        (new AuthService())->startSession($user);

        return redirect()->to(site_url('account/bookings/' . (int) $booking['id']))->with('alert', [
            'type' => 'success',
            'title' => 'Booking added',
            'message' => 'Booking ' . $booking['reference_code'] . ' is now in your account, ' . $user['name'] . '.',
        ]);
    }

    /** Shown after paying. Identical whether or not the email already has an account. */
    public function confirmed(int $id): string
    {
        $booking = (new BookingLinkService())->hasGuestAccess($id) ? (new BookingModel())->find($id) : null;
        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound('Booking not found.');
        }

        $row = (new TransactionModel())->selectSum('amount')->where('booking_id', $id)->where('status', 'completed')->first();
        $balance = max(0, round((float) $booking['total_amount'] - round((float) ($row['amount'] ?? 0), 2), 2));

        return view('account/booking_confirmed', [
            ...$this->pageData('Booking Confirmed | EPIC Storage Solutions', 'Your EPIC Storage booking is confirmed.'),
            'booking' => $booking,
            'balance' => $balance,
            'maskedEmail' => BookingLinkService::maskEmail((string) $booking['customer_email']),
        ]);
    }

    private function sendBookingReceivedEmail(int $bookingId): void
    {
        $booking = (new BookingModel())->find($bookingId);
        if ($booking === null) {
            return;
        }

        try {
            $links = new BookingLinkService();
            (new SmtpService())->sendGuestBookingReceived(
                $booking,
                (new BookingItemModel())->where('booking_id', $bookingId)->findAll(),
                $booking['dorm_id'] ? (new DormModel())->find((int) $booking['dorm_id']) : null,
                (float) $booking['total_amount'] > 0 ? $links->paymentUrl($booking) : null,
                $links->accountLink($booking, '/account/bookings/' . $bookingId),
            );
        } catch (Throwable $exception) {
            // The booking is saved either way; staff can resend from the admin booking page.
            log_message('error', 'Booking received email for booking {booking} failed: {type}', ['booking' => $bookingId, 'type' => $exception::class]);
        }
    }

    private function isCustomerLoggedIn(): bool
    {
        return (int) session()->get('customer_user_id') > 0 && session()->get('customer_is_logged_in') === true;
    }

    /** @return array<string, string|null> */
    private function pageData(string $title, string $description): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'activePage' => 'services',
            'canonicalUrl' => current_url(),
            'socialImage' => null,
            'robots' => 'noindex,nofollow',
        ];
    }
}
