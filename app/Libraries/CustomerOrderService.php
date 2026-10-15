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
        try {
            $this->lockCustomer($userId);
            $booking = (new BookingModel())->where('id', $bookingId)->where('user_id', $userId)->first();
            if ($booking === null || in_array($booking['status'], ['completed', 'cancelled'], true)) {
                throw new InvalidArgumentException('This order is closed. Contact EPIC for assistance.');
            }
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
            $quote = (new BookingPricingService())->quote($booking['booking_type'], max($booking['booking_type'] === 'summer_advantage' ? 1 : 0, $boxes), false, false, $requestedItems);
            if ($boxes === 0 && $quote['items'] === []) {
                throw new InvalidArgumentException('Choose at least one additional box or item.');
            }
            foreach ($quote['items'] as $line) {
                if ((new BookingItemModel())->insert([...$line, 'booking_id' => $bookingId]) === false) {
                    throw new RuntimeException('Unable to append order item.');
                }
            }
            if (! (new BookingModel())->update($bookingId, ['additional_box_quantity' => (int) $booking['additional_box_quantity'] + $boxes])) {
                throw new RuntimeException('Unable to update order boxes.');
            }
            (new BookingService())->recalculate($bookingId);
            $db->table('booking_additions')->insert(['booking_id' => $bookingId, 'request_token' => $token, 'created_at' => date('Y-m-d H:i:s')]);
            if (! $db->transStatus()) {
                throw new RuntimeException('Unable to save additional items.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }
    }
}
