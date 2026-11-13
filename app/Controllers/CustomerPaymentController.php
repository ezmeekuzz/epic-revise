<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BookingCharge;
use App\Libraries\BookingLinkService;
use App\Libraries\PaymentService;
use App\Libraries\SquarePaymentGateway;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Square checkout for one booking. Serves two entry points with the same page and logic:
 * - account: the booking must belong to the logged-in customer;
 * - guest:   the booking was created in this browser session, or opened from an emailed pay link.
 */
final class CustomerPaymentController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function show(int $id): string|ResponseInterface
    {
        return $this->renderPayment($this->ownedBooking($id), false);
    }

    public function guestShow(int $id): string|ResponseInterface
    {
        return $this->renderPayment($this->guestBooking($id), true);
    }

    public function process(int $id): ResponseInterface
    {
        return $this->processPayment($this->ownedBooking($id), false);
    }

    public function guestProcess(int $id): ResponseInterface
    {
        return $this->processPayment($this->guestBooking($id), true);
    }

    /** @param array<string, mixed> $booking */
    private function renderPayment(array $booking, bool $guest): string|ResponseInterface
    {
        $id = (int) $booking['id'];
        $balance = $this->outstandingBalance($booking);
        if ($balance < 0.01) {
            return redirect()->to($this->doneUrl($id, $guest))->with('success', 'This booking is already paid.');
        }

        $configuration = (new SquarePaymentGateway())->clientConfiguration();

        return view('account/payment', [
            ...$this->pageData('Secure Payment | EPIC Storage Solutions', 'Pay securely for your EPIC Storage booking.', 'account'),
            'layout' => $guest ? 'layouts/site' : 'layouts/account',
            'guest' => $guest,
            'booking' => $booking,
            'balance' => $balance,
            'square' => $configuration,
            'clientConfig' => [
                'applicationId' => $configuration['applicationId'],
                'locationId' => $configuration['locationId'],
                'currency' => $configuration['currency'],
                'amount' => number_format($balance, 2, '.', ''),
                'paymentKey' => $this->paymentKey($id),
                'endpoint' => site_url($guest ? 'book/' . $id . '/pay' : 'account/bookings/' . $id . '/payment'),
                'successUrl' => $this->doneUrl($id, $guest),
                'csrfHeader' => config('Security')->headerName,
                'csrfToken' => csrf_hash(),
                'billingContact' => [
                    'givenName' => (string) ($booking['customer_name'] ?? ''),
                    'email' => (string) ($booking['customer_email'] ?? ''),
                    'phone' => (string) ($booking['customer_phone'] ?? ''),
                ],
            ],
        ]);
    }

    /** @param array<string, mixed> $booking */
    private function processPayment(array $booking, bool $guest): ResponseInterface
    {
        $id = (int) $booking['id'];
        $doneUrl = $this->doneUrl($id, $guest);
        $configuration = (new SquarePaymentGateway())->clientConfiguration();
        if (! $configuration['ready']) {
            return $this->response->setStatusCode(503)->setJSON(['status' => 'error', 'message' => 'Square payment is not available yet. Your booking remains saved.']);
        }
        if (in_array($booking['status'], ['cancelled'], true)) {
            return $this->response->setStatusCode(409)->setJSON(['status' => 'error', 'message' => 'A cancelled booking cannot be paid.']);
        }

        $input = $this->request->getJSON(true);
        if (! is_array($input)) {
            $input = [];
        }
        $sourceId = trim((string) ($input['source_id'] ?? ''));
        $submittedKey = trim((string) ($input['payment_key'] ?? ''));
        $sessionKey = (string) session()->get($this->sessionKey($id));
        if ($sourceId === '' || $submittedKey === '' || $sessionKey === '' || ! hash_equals($sessionKey, $submittedKey)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'The payment session is invalid or expired. Refresh the page and try again.']);
        }

        $result = (new BookingCharge())->run($booking, $sourceId, $submittedKey, $guest);
        if ($result['ok']) {
            session()->remove($this->sessionKey($id));

            return $this->response->setJSON(['status' => 'success', 'message' => $result['message'], 'redirect' => $doneUrl]);
        }
        if ($result['reload']) {
            session()->remove($this->sessionKey($id));
        }

        return $this->response->setStatusCode($result['code'])->setJSON(['status' => 'error', 'message' => $result['message'], 'reload' => $result['reload']]);
    }

    private function doneUrl(int $id, bool $guest): string
    {
        return site_url($guest ? 'book/' . $id . '/confirmed' : 'account/bookings/' . $id);
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

    /**
     * A booking ID alone never grants access: the session must have created it or opened its pay link.
     *
     * @return array<string, mixed>
     */
    private function guestBooking(int $id): array
    {
        $booking = (new BookingLinkService())->hasGuestAccess($id) ? (new BookingModel())->find($id) : null;
        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound('Booking not found.');
        }

        return $booking;
    }

    /** @param array<string, mixed> $booking */
    private function outstandingBalance(array $booking): float
    {
        $row = (new TransactionModel())->selectSum('amount')->where('booking_id', (int) $booking['id'])->where('status', 'completed')->first();
        $paid = round((float) ($row['amount'] ?? 0), 2);

        return max(0, round((float) $booking['total_amount'] - $paid, 2));
    }

    private function paymentKey(int $bookingId): string
    {
        $sessionKey = $this->sessionKey($bookingId);
        $key = (string) session()->get($sessionKey);
        if ($key === '') {
            $key = 'EPIC-' . $bookingId . '-' . bin2hex(random_bytes(12));
            session()->set($sessionKey, $key);
        }

        return $key;
    }

    private function sessionKey(int $bookingId): string
    {
        return 'square_payment_key_' . $bookingId;
    }

    /** @return array<string, string|null> */
    private function pageData(string $title, string $description, string $activePage): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'activePage' => $activePage,
            'canonicalUrl' => current_url(),
            'socialImage' => null,
            'robots' => 'noindex,nofollow',
        ];
    }
}
