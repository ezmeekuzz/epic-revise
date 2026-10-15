<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\PaymentService;
use App\Libraries\SquarePaymentGateway;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

final class CustomerPaymentController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function show(int $id): string|ResponseInterface
    {
        $booking = $this->ownedBooking($id);
        $balance = $this->outstandingBalance($booking);
        if ($balance < 0.01) {
            return redirect()->route('account.bookings.show', [$id])->with('success', 'This booking is already paid.');
        }

        $gateway = new SquarePaymentGateway();
        $configuration = $gateway->clientConfiguration();
        $paymentKey = $this->paymentKey($id);
        $user = (new UserModel())->find((int) session()->get('customer_user_id'));

        return view('account/payment', [
            ...$this->pageData('Secure Payment | Epic Storage Solutions', 'Pay securely for your Epic Storage booking.', 'account'),
            'booking' => $booking,
            'user' => $user,
            'balance' => $balance,
            'square' => $configuration,
            'clientConfig' => [
                'applicationId' => $configuration['applicationId'],
                'locationId' => $configuration['locationId'],
                'currency' => $configuration['currency'],
                'amount' => number_format($balance, 2, '.', ''),
                'paymentKey' => $paymentKey,
                'endpoint' => site_url('account/bookings/' . $id . '/payment'),
                'successUrl' => site_url('account/bookings/' . $id),
                'csrfHeader' => config('Security')->headerName,
                'csrfToken' => csrf_hash(),
                'billingContact' => [
                    'givenName' => (string) ($user['name'] ?? ''),
                    'email' => (string) ($user['email'] ?? ''),
                    'phone' => (string) ($user['phone_number'] ?? ''),
                ],
            ],
        ]);
    }

    public function process(int $id): ResponseInterface
    {
        $booking = $this->ownedBooking($id);
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

        $balance = $this->outstandingBalance($booking);
        if ($balance < 0.01) {
            session()->remove($this->sessionKey($id));

            return $this->response->setJSON(['status' => 'success', 'message' => 'This booking is already paid.', 'redirect' => site_url('account/bookings/' . $id)]);
        }

        $payments = new PaymentModel();
        $payment = $payments->where('reference', $submittedKey)->first();
        try {
            if ($payment === null) {
                $paymentId = (new PaymentService())->create([
                    'booking_id' => $id,
                    'user_id' => (int) session()->get('customer_user_id'),
                    'reference' => $submittedKey,
                    'provider' => 'square',
                    'method' => 'card',
                    'amount' => number_format($balance, 2, '.', ''),
                    'currency' => $configuration['currency'],
                    'status' => 'pending',
                    'notes' => 'Customer-initiated Square payment.',
                ]);
                $payment = $payments->find($paymentId);
            }
            if ($payment === null) {
                throw new RuntimeException('The payment attempt could not be created.');
            }
            if ($payment['status'] === 'completed') {
                session()->remove($this->sessionKey($id));

                return $this->response->setJSON(['status' => 'success', 'message' => 'Payment completed.', 'redirect' => site_url('account/bookings/' . $id)]);
            }
            if ($payment['status'] !== 'pending') {
                throw new RuntimeException('This payment attempt can no longer be processed. Refresh the page and try again.');
            }

            $result = (new SquarePaymentGateway())->charge(
                $sourceId,
                // Retries retain the amount saved with this idempotency key.
                // Additional items may have increased the order balance since the attempt began.
                (int) round((float) $payment['amount'] * 100),
                $configuration['currency'],
                $submittedKey,
                (string) $booking['reference_code'],
            );
            if (! $result['success']) {
                $payments->update((int) $payment['id'], ['status' => 'failed', 'notes' => (string) ($result['message'] ?? 'Square payment failed.')]);
                session()->remove($this->sessionKey($id));

                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => (string) ($result['message'] ?? 'Square could not complete the payment.'),
                    'reload' => true,
                ]);
            }

            $payments->update((int) $payment['id'], [
                'external_reference' => $result['paymentId'],
                'notes' => $result['receiptUrl'] !== '' ? 'Square receipt: ' . $result['receiptUrl'] : 'Square payment completed.',
            ]);
            $currentPayment = $payments->find((int) $payment['id']);
            if (($currentPayment['status'] ?? '') !== 'completed') {
                (new PaymentService())->complete((int) $payment['id']);
            }
            session()->remove($this->sessionKey($id));

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Payment completed successfully.',
                'redirect' => site_url('account/bookings/' . $id),
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Customer Square payment persistence failed for booking {booking}: {type}', [
                'booking' => $id,
                'type' => $exception::class,
            ]);

            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'The payment could not be confirmed. Please check your booking before trying again.']);
        }
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
