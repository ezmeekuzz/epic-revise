<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\PaymentModel;
use App\Models\TransactionModel;
use RuntimeException;

final class PaymentService
{
    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $db       = db_connect();
        $payments = new PaymentModel();
        $db->transStart();

        $paymentId = (int) $payments->insert($data, true);
        if (($data['status'] ?? '') === 'completed') {
            $this->recordTransaction($paymentId, (int) $data['booking_id'], 'payment', (float) $data['amount'], (string) $data['currency'], 'Payment received');
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new RuntimeException('The payment could not be recorded.');
        }

        (new BookingService())->refreshPaymentStatus((int) $data['booking_id']);

        return $paymentId;
    }

    public function complete(int $paymentId): void
    {
        $payments = new PaymentModel();
        $payment  = $payments->find($paymentId);
        if ($payment === null || $payment['status'] !== 'pending') {
            throw new RuntimeException('Only pending payments can be completed.');
        }

        $db = db_connect();
        $db->transStart();
        $payments->update($paymentId, ['status' => 'completed', 'paid_at' => date('Y-m-d H:i:s')]);
        $this->recordTransaction($paymentId, (int) $payment['booking_id'], 'payment', (float) $payment['amount'], (string) $payment['currency'], 'Payment received');
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new RuntimeException('The payment could not be completed.');
        }

        (new BookingService())->refreshPaymentStatus((int) $payment['booking_id']);
    }

    public function refund(int $paymentId): void
    {
        $payments = new PaymentModel();
        $payment  = $payments->find($paymentId);
        if ($payment === null || $payment['status'] !== 'completed') {
            throw new RuntimeException('Only completed payments can be refunded.');
        }

        $db = db_connect();
        $db->transStart();
        $payments->update($paymentId, ['status' => 'refunded']);
        $this->recordTransaction($paymentId, (int) $payment['booking_id'], 'refund', -1 * (float) $payment['amount'], (string) $payment['currency'], 'Payment refunded');
        $db->transComplete();
        if (! $db->transStatus()) {
            throw new RuntimeException('The refund could not be recorded.');
        }

        (new BookingService())->refreshPaymentStatus((int) $payment['booking_id']);
    }

    private function recordTransaction(int $paymentId, int $bookingId, string $type, float $amount, string $currency, string $description): void
    {
        (new TransactionModel())->insert([
            'payment_id'  => $paymentId,
            'booking_id'  => $bookingId,
            'reference'   => 'TXN-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'type'        => $type,
            'amount'      => number_format($amount, 2, '.', ''),
            'currency'    => $currency,
            'status'      => 'completed',
            'description' => $description,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
