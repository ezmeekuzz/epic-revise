<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use RuntimeException;
use Throwable;

/**
 * Charges a booking's outstanding balance with a Square token. Shared by the pay page
 * and by the single-page booking checkout, so both behave identically.
 */
final class BookingCharge
{
    /**
     * @param array<string, mixed> $booking
     * @return array{ok: bool, message: string, code: int, reload: bool}
     */
    public function run(array $booking, string $sourceId, string $paymentKey, bool $guest): array
    {
        $id = (int) $booking['id'];
        $configuration = (new SquarePaymentGateway())->clientConfiguration();
        if (! $configuration['ready']) {
            return $this->result(false, 'Square payment is not available yet. Your booking remains saved.', 503);
        }
        if ($booking['status'] === 'cancelled') {
            return $this->result(false, 'A cancelled booking cannot be paid.', 409);
        }

        $balance = $this->outstandingBalance($booking);
        if ($balance < 0.01) {
            return $this->result(true, 'This booking is already paid.');
        }

        $payments = new PaymentModel();
        $payment = $payments->where('reference', $paymentKey)->first();
        try {
            if ($payment === null) {
                $paymentId = (new PaymentService())->create([
                    'booking_id' => $id,
                    // A guest booking has no owner yet; linking it to an account later updates this too.
                    'user_id' => ! empty($booking['user_id']) ? (int) $booking['user_id'] : null,
                    'reference' => $paymentKey,
                    'provider' => 'square',
                    'method' => 'card',
                    'amount' => number_format($balance, 2, '.', ''),
                    'currency' => $configuration['currency'],
                    'status' => 'pending',
                    'notes' => $guest ? 'Customer-initiated Square payment (no account).' : 'Customer-initiated Square payment.',
                ]);
                $payment = $payments->find($paymentId);
            }
            if ($payment === null) {
                throw new RuntimeException('The payment attempt could not be created.');
            }
            if ($payment['status'] === 'completed') {
                return $this->result(true, 'Payment completed.');
            }
            if ($payment['status'] !== 'pending') {
                throw new RuntimeException('This payment attempt can no longer be processed. Refresh the page and try again.');
            }

            $result = (new SquarePaymentGateway())->charge(
                $sourceId,
                // Retries retain the amount saved with this idempotency key.
                (int) round((float) $payment['amount'] * 100),
                $configuration['currency'],
                $paymentKey,
                (string) $booking['reference_code'],
            );
            if (! $result['success']) {
                $payments->update((int) $payment['id'], ['status' => 'failed', 'notes' => (string) ($result['message'] ?? 'Square payment failed.')]);

                return $this->result(false, (string) ($result['message'] ?? 'Square could not complete the payment.'), 422, true);
            }

            $payments->update((int) $payment['id'], [
                'external_reference' => $result['paymentId'],
                'notes' => $result['receiptUrl'] !== '' ? 'Square receipt: ' . $result['receiptUrl'] : 'Square payment completed.',
            ]);
            $current = $payments->find((int) $payment['id']);
            if (($current['status'] ?? '') !== 'completed') {
                (new PaymentService())->complete((int) $payment['id']);
            }

            return $this->result(true, 'Payment completed successfully.');
        } catch (Throwable $exception) {
            log_message('error', 'Customer Square payment persistence failed for booking {booking}: {type}', ['booking' => $id, 'type' => $exception::class]);

            return $this->result(false, 'The payment could not be confirmed. Please check your booking before trying again.', 500);
        }
    }

    /** @param array<string, mixed> $booking */
    public function outstandingBalance(array $booking): float
    {
        $row = (new TransactionModel())->selectSum('amount')->where('booking_id', (int) $booking['id'])->where('status', 'completed')->first();

        return max(0, round((float) $booking['total_amount'] - round((float) ($row['amount'] ?? 0), 2), 2));
    }

    /** @return array{ok: bool, message: string, code: int, reload: bool} */
    private function result(bool $ok, string $message, int $code = 200, bool $reload = false): array
    {
        return ['ok' => $ok, 'message' => $message, 'code' => $code, 'reload' => $reload];
    }
}
