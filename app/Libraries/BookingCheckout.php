<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\UserModel;
use RuntimeException;
use Throwable;

/**
 * Saves a validated, priced customer booking. Used by both the logged-in booking form
 * and the public (no-account) booking form, so they always share the same rules.
 */
final class BookingCheckout
{
    /**
     * @param array<string, mixed> $data  Validated booking columns.
     * @param array<string, mixed> $quote Result of BookingPricingService::quote().
     * @return array{status: 'created'|'existing', id: int}
     */
    public function create(array $data, array $quote, ?int $userId): array
    {
        $db = db_connect();
        $db->transBegin();

        try {
            if ($userId !== null) {
                $orders = new CustomerOrderService();
                $orders->lockCustomer($userId);
                $active = $orders->activeOrder($userId);
                if ($active !== null) {
                    $db->transRollback();

                    return ['status' => 'existing', 'id' => (int) $active['id']];
                }
            }

            $bookingService = new BookingService();
            $bookingId = (int) (new BookingModel())->insert([
                ...$data,
                'terms_snapshot' => (new SettingsService())->get('current_terms_text', '') ?: strip_tags(view('partials/legacy_terms')),
                'terms_accepted_at' => date('Y-m-d H:i:s'),
                'reference_code' => $bookingService->reference(),
                'ordernumber' => $bookingService->orderNumber(),
                'booking_date' => date('Y-m-d'),
                'user_id' => $userId,
                'status' => 'pending',
                'base_price' => $quote['base_price'],
                'additional_box_quantity' => $quote['box_quantity'],
                'additional_box_amount' => $quote['box_price'],
                'addtl_box_total_amount' => $quote['box_total'],
                'is_study_abroad' => $quote['is_study_abroad'] ? 1 : 0,
                'study_abroad_additional_storage_price' => $quote['study_abroad_surcharge'],
                'is_summer_school' => $quote['is_summer_school'] ? 1 : 0,
                'summer_school_fee' => $quote['summer_school_fee'],
                'payment_status' => 'unpaid',
                'subtotal' => $quote['subtotal'],
                'discount_amount' => '0.00',
                'total_amount' => $quote['total'],
            ], true);
            if ($bookingId < 1) {
                throw new RuntimeException('The booking could not be saved.');
            }

            $now = date('Y-m-d H:i:s');
            foreach ($quote['items'] as $line) {
                if ((new BookingItemModel())->insert([...$line, 'booking_id' => $bookingId, 'created_at' => $now, 'updated_at' => $now]) === false) {
                    throw new RuntimeException('A selected item could not be saved.');
                }
            }

            // "Ask each detail once": the booking form keeps the customer's saved profile current.
            if ($userId !== null && ! (new UserModel())->update($userId, [
                'student_id' => $data['student_id'],
                'phone_number' => $data['customer_phone'],
                'parent_phone_number' => $data['parent_phone_number'],
                'parent_email_address' => $data['parent_email_address'],
            ])) {
                throw new RuntimeException('The customer profile could not be updated.');
            }

            if (! $db->transStatus()) {
                throw new RuntimeException('The booking transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }

        return ['status' => 'created', 'id' => $bookingId];
    }
}
