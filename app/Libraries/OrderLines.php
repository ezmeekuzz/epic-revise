<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * One description for every charge on an order, shared by the booking page, the emails,
 * the PDF and the admin booking view so they never disagree.
 */
final class OrderLines
{
    public const BASE_PACKAGE_LABEL = 'Standard Package (5 lg boxes / Base Package)';

    /**
     * @param array<string, mixed>       $booking
     * @param list<array<string, mixed>> $items
     * @return list<array{label: string, amount: float, kind: string}>
     */
    public static function build(array $booking, array $items): array
    {
        $isStorage = ($booking['booking_type'] ?? '') === 'summer_storage';
        $lines     = [];

        if ($isStorage) {
            $lines[] = ['label' => self::BASE_PACKAGE_LABEL, 'amount' => (float) ($booking['base_price'] ?? 0), 'kind' => 'base'];
            foreach ($items as $item) {
                $size    = trim((string) ($item['size_name'] ?? ''));
                $lines[] = [
                    'label'  => trim((string) $item['item_name'] . ($size !== '' ? ' (' . $size . ')' : '')) . ' × ' . (int) $item['quantity'] . ' @ $' . number_format((float) $item['unit_price'], 2) . ' each',
                    'amount' => (float) $item['line_total'],
                    'kind'   => 'item',
                ];
            }
        }

        $boxes = (int) ($booking['additional_box_quantity'] ?? 0);
        $boxAmount = (float) ($booking['addtl_box_total_amount'] ?? 0);
        if ($boxes > 0 || $boxAmount > 0 || ! $isStorage) {
            $lines[] = [
                'label'  => ($isStorage ? 'Additional boxes' : 'Summer Advantage boxes') . ' × ' . $boxes . ' @ $' . number_format((float) ($booking['additional_box_amount'] ?? 0), 2) . ($isStorage ? ' each' : ' per box'),
                'amount' => $boxAmount,
                'kind'   => 'boxes',
            ];
        }

        if ($isStorage && (int) ($booking['is_summer_school'] ?? 0) === 1) {
            $lines[] = ['label' => 'Summer school fee', 'amount' => (float) ($booking['summer_school_fee'] ?? 0), 'kind' => 'summer_school'];
        }
        if ($isStorage && (int) ($booking['is_study_abroad'] ?? 0) === 1) {
            $lines[] = ['label' => 'Study abroad: second-semester storage (doubles the order subtotal)', 'amount' => (float) ($booking['study_abroad_additional_storage_price'] ?? 0), 'kind' => 'study_abroad'];
        }
        if ((float) ($booking['discount_amount'] ?? 0) > 0) {
            $lines[] = ['label' => 'Discount', 'amount' => -(float) $booking['discount_amount'], 'kind' => 'discount'];
        }
        if ((float) ($booking['service_fee_amount'] ?? 0) > 0) {
            $lines[] = ['label' => 'Credit card service fee (' . self::percent((float) ($booking['service_fee_percent'] ?? 0)) . '%)', 'amount' => (float) $booking['service_fee_amount'], 'kind' => 'service_fee'];
        }

        return $lines;
    }

    public static function percent(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    public static function money(float $amount): string
    {
        return ($amount < 0 ? '-$' : '$') . number_format(abs($amount), 2);
    }
}
