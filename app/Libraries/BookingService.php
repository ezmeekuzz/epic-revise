<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\TransactionModel;

final class BookingService
{
    public function reference(): string
    {
        $bookings = new BookingModel();

        do {
            $reference = 'EPIC-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($bookings->where('reference_code', $reference)->first() !== null);

        return $reference;
    }

    public function orderNumber(): string
    {
        return date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function recalculate(int $bookingId): void
    {
        $items    = new BookingItemModel();
        $bookings = new BookingModel();
        $booking  = $bookings->find($bookingId);

        if ($booking === null) {
            return;
        }

        $result = $items->selectSum('line_total')->where('booking_id', $bookingId)->first();
        $lineItems = $booking['booking_type'] === 'summer_storage' ? (float) ($result['line_total'] ?? 0) : 0.0;
        $additionalBoxes = (int) ($booking['additional_box_quantity'] ?? 0) * (float) ($booking['additional_box_amount'] ?? 0);
        $basePrice = $booking['booking_type'] === 'summer_storage' ? (float) ($booking['base_price'] ?? 0) : 0.0;
        $summerSchoolFee = $booking['booking_type'] === 'summer_storage' && (int) ($booking['is_summer_school'] ?? 0) === 1
            ? (float) ($booking['summer_school_fee'] ?? 0)
            : 0.0;
        $serviceSubtotal = $basePrice + $lineItems + $additionalBoxes + $summerSchoolFee;
        $studyAbroadPrice = $booking['booking_type'] === 'summer_storage' && (int) ($booking['is_study_abroad'] ?? 0) === 1
            ? $serviceSubtotal
            : 0.0;
        $subtotal = $serviceSubtotal + $studyAbroadPrice;
        $discount = max((float) $booking['discount_amount'], 0);
        $appliedDiscount = min($discount, $subtotal);

        $bookings->update($bookingId, [
            'base_price' => number_format($basePrice, 2, '.', ''),
            'addtl_box_total_amount' => number_format($additionalBoxes, 2, '.', ''),
            'summer_school_fee' => number_format($summerSchoolFee, 2, '.', ''),
            'study_abroad_additional_storage_price' => number_format($studyAbroadPrice, 2, '.', ''),
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount_amount' => number_format($discount, 2, '.', ''),
            'total_amount' => number_format($subtotal - $appliedDiscount, 2, '.', ''),
        ]);

        $this->refreshPaymentStatus($bookingId);
    }

    public function refreshPaymentStatus(int $bookingId): void
    {
        $booking = (new BookingModel())->find($bookingId);
        if ($booking === null) {
            return;
        }

        $row = (new TransactionModel())
            ->selectSum('amount')
            ->where('booking_id', $bookingId)
            ->where('status', 'completed')
            ->first();
        $paid  = round((float) ($row['amount'] ?? 0), 2);
        $total = round((float) $booking['total_amount'], 2);
        $status = $paid <= 0 ? 'unpaid' : ($paid + 0.001 >= $total ? 'paid' : 'partial');

        (new BookingModel())->update($bookingId, ['payment_status' => $status]);
    }
}
