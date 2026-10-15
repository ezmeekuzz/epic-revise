<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingModel;
use App\Models\BookingItemModel;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class CustomerOrderService
{
    public function activeOrder(int $userId): ?array
    {
        return (new BookingModel())->where('user_id', $userId)
            ->whereNotIn('status', ['completed', 'cancelled'])->orderBy('id', 'DESC')->first();
    }

    /** Serialize order creation for one customer, including concurrent submissions. */
    public function lockCustomer(int $userId): void
    {
        $db = db_connect();
        $db->query('SELECT id FROM ' . $db->protectIdentifiers($db->prefixTable('users')) . ' WHERE id = ? FOR UPDATE', [$userId]);
    }

    /** Append validated catalog items without replacing the original prices or payments. */
    public function add(int $userId, int $bookingId, int $boxes, array $requestedItems, string $token): void
    {
        if ($boxes < 0 || $boxes > 100 || ! preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new InvalidArgumentException('The additional item request is invalid. Refresh the page and try again.');
        }
        $db = db_connect();
        $db->transBegin();
        $stage = 'lock_customer';
        try {
            $this->lockCustomer($userId);
            $stage = 'load_order';
            $booking = (new BookingModel())->where('id', $bookingId)->where('user_id', $userId)->first();
            if ($booking === null || in_array($booking['status'], ['completed', 'cancelled'], true)) {
                throw new InvalidArgumentException('This order is closed. Contact EPIC for assistance.');
            }
            $stage = 'check_duplicate_request';
            if ($db->table('booking_additions')->where('request_token', $token)->countAllResults() > 0) {
                $db->transCommit();
                return;
            }
            if ((int) $booking['additional_box_quantity'] + $boxes > 100) {
                throw new InvalidArgumentException('The total additional box quantity cannot exceed 100.');
            }
            if ($booking['booking_type'] === 'summer_advantage' && $requestedItems !== []) {
                throw new InvalidArgumentException('Summer Advantage supports additional boxes only.');
            }
            $stage = 'price_items';
            $quote = (new BookingPricingService())->quote($booking['booking_type'], max($booking['booking_type'] === 'summer_advantage' ? 1 : 0, $boxes), false, false, $requestedItems);
            if ($boxes === 0 && $quote['items'] === []) {
                throw new InvalidArgumentException('Choose at least one additional box or item.');
            }
            foreach ($quote['items'] as $line) {
                $stage = 'insert_item';
                if ((new BookingItemModel())->insert([...$line, 'booking_id' => $bookingId]) === false) {
                    throw new RuntimeException('Unable to append order item.');
                }
            }
            $stage = 'update_boxes';
            if (! (new BookingModel())->update($bookingId, ['additional_box_quantity' => (int) $booking['additional_box_quantity'] + $boxes])) {
                throw new RuntimeException('Unable to update order boxes.');
            }
            $stage = 'recalculate_balance';
            (new BookingService())->recalculate($bookingId);
            $stage = 'record_request';
            if (! $db->table('booking_additions')->insert(['booking_id' => $bookingId, 'request_token' => $token, 'created_at' => date('Y-m-d H:i:s')])) {
                throw new RuntimeException('Unable to record the additional-item request.');
            }
            if (! $db->transStatus()) {
                throw new RuntimeException('Unable to save additional items.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            // Capture database details before rollback clears the driver error.
            // Never log the request token, customer form values, or payment credentials.
            $databaseError = $db->error();
            if (! $exception instanceof InvalidArgumentException) {
                $details = [];
                for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
                    $details[] = $cause::class . ': ' . $cause->getMessage();
                }
                $redact = static fn (string $message): string => str_replace($token, '[request token redacted]', $message);
                $trace = array_map(static function (array $frame): string {
                    return sprintf('%s:%d %s%s%s', $frame['file'] ?? '[internal]', $frame['line'] ?? 0,
                        $frame['class'] ?? '', $frame['type'] ?? '', $frame['function'] ?? '');
                }, $exception->getTrace());
                log_message('error', 'Order additions failed for booking {booking} at {stage}: {detail}; database error {db_code}: {db_message}; {file}:{line}; trace: {trace}', [
                    'booking' => $bookingId, 'stage' => $stage,
                    'detail' => $redact(implode(' | ', $details)),
                    'db_code' => $databaseError['code'] ?? 0,
                    'db_message' => $redact((string) ($databaseError['message'] ?? '')),
                    'file' => $exception->getFile(), 'line' => $exception->getLine(),
                    'trace' => implode(' <- ', $trace),
                ]);
            }
            $db->transRollback();
            throw $exception;
        }
    }
}
