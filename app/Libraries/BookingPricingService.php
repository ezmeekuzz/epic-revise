<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\ItemSizeModel;
use InvalidArgumentException;

final class BookingPricingService
{
    /** @return list<array<string, mixed>> */
    public function catalog(): array
    {
        $rows = (new ItemSizeModel())
            ->select('item_sizes.id, item_sizes.item_id, item_sizes.name AS size_name, item_sizes.dimensions, item_sizes.unit_price, items.name AS item_name')
            ->join('items', 'items.id = item_sizes.item_id')
            ->where('item_sizes.is_active', 1)
            ->where('items.is_active', 1)
            ->where('items.deleted_at', null)
            ->orderBy('items.name', 'ASC')
            ->orderBy('item_sizes.unit_price', 'ASC')
            ->findAll();

        $catalog = [];
        foreach ($rows as $row) {
            $itemId = (int) $row['item_id'];
            if (! isset($catalog[$itemId])) {
                $catalog[$itemId] = [
                    'id' => $itemId,
                    'name' => (string) $row['item_name'],
                    'sizes' => [],
                ];
            }

            $catalog[$itemId]['sizes'][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['size_name'],
                'dimensions' => (string) ($row['dimensions'] ?? ''),
                'unit_price' => $this->format($this->moneyToCents((string) $row['unit_price'])),
            ];
        }

        return array_values($catalog);
    }

    /**
     * @param array<int|string, mixed> $requestedItems
     * @return array<string, mixed>
     */
    public function quote(
        string $bookingType,
        int $boxQuantity,
        bool $summerSchool,
        bool $studyAbroad,
        array $requestedItems,
    ): array {
        if (! in_array($bookingType, ['summer_storage', 'summer_advantage'], true)) {
            throw new InvalidArgumentException('Choose a valid EPIC service.');
        }

        if ($boxQuantity < ($bookingType === 'summer_advantage' ? 1 : 0) || $boxQuantity > 100) {
            throw new InvalidArgumentException($bookingType === 'summer_advantage'
                ? 'Summer Advantage requires between 1 and 100 boxes.'
                : 'Additional boxes must be between 0 and 100.');
        }

        $settings = new SettingsService();
        $baseCents = $bookingType === 'summer_storage'
            ? $this->moneyToCents($settings->get('summer_storage_base_price', '450.00'))
            : 0;
        // Both services intentionally share one operator-controlled box price.
        $boxPriceCents = $this->moneyToCents($settings->get('summer_advantage_box_price', '50.00'));

        if ($bookingType === 'summer_advantage') {
            $summerSchool = false;
            $studyAbroad = false;
            $requestedItems = [];
        }

        $lines = $bookingType === 'summer_storage' ? $this->resolveItems($requestedItems) : [];
        $itemsCents = array_sum(array_column($lines, 'line_total_cents'));
        $boxTotalCents = $boxQuantity * $boxPriceCents;
        $summerSchoolCents = $summerSchool
            ? $this->moneyToCents($settings->get('summer_school_fee', '75.00'))
            : 0;
        $serviceSubtotalCents = $baseCents + $boxTotalCents + $itemsCents + $summerSchoolCents;
        $studyAbroadCents = $studyAbroad ? $serviceSubtotalCents : 0;
        $totalCents = $serviceSubtotalCents + $studyAbroadCents;

        return [
            'booking_type' => $bookingType,
            'box_quantity' => $boxQuantity,
            'is_summer_school' => $summerSchool,
            'is_study_abroad' => $studyAbroad,
            'base_price' => $this->format($baseCents),
            'box_price' => $this->format($boxPriceCents),
            'box_total' => $this->format($boxTotalCents),
            'items_total' => $this->format($itemsCents),
            'summer_school_fee' => $this->format($summerSchoolCents),
            'service_subtotal' => $this->format($serviceSubtotalCents),
            'study_abroad_surcharge' => $this->format($studyAbroadCents),
            'subtotal' => $this->format($totalCents),
            'total' => $this->format($totalCents),
            'items' => array_map(static function (array $line): array {
                unset($line['line_total_cents']);

                return $line;
            }, $lines),
        ];
    }

    /** @return array<string, string> */
    public function displayPrices(): array
    {
        $settings = new SettingsService();

        $sharedBoxPrice = $this->format($this->moneyToCents($settings->get('summer_advantage_box_price', '50.00')));

        return [
            'summer_storage_base_price' => $this->format($this->moneyToCents($settings->get('summer_storage_base_price', '450.00'))),
            'summer_storage_additional_box_price' => $sharedBoxPrice,
            'summer_advantage_box_price' => $sharedBoxPrice,
            'summer_school_fee' => $this->format($this->moneyToCents($settings->get('summer_school_fee', '75.00'))),
        ];
    }

    /**
     * @param array<int|string, mixed> $requestedItems
     * @return list<array<string, mixed>>
     */
    private function resolveItems(array $requestedItems): array
    {
        if (count($requestedItems) > 50) {
            throw new InvalidArgumentException('A booking can contain no more than 50 additional item selections.');
        }

        $quantities = [];
        foreach ($requestedItems as $requested) {
            if (! is_array($requested)) {
                throw new InvalidArgumentException('One or more additional item selections are invalid.');
            }

            $sizeId = (int) ($requested['size_id'] ?? 0);
            $quantity = (int) ($requested['quantity'] ?? 0);
            if ($sizeId < 1 || $quantity < 1) {
                throw new InvalidArgumentException('Every additional item needs an available size and quantity.');
            }

            $quantities[$sizeId] = ($quantities[$sizeId] ?? 0) + $quantity;
            if ($quantities[$sizeId] > 100) {
                throw new InvalidArgumentException('An additional item quantity cannot exceed 100.');
            }
        }

        if ($quantities === []) {
            return [];
        }

        $sizes = (new ItemSizeModel())
            ->select('item_sizes.id, item_sizes.item_id, item_sizes.name AS size_name, item_sizes.unit_price, items.name AS item_name')
            ->join('items', 'items.id = item_sizes.item_id')
            ->whereIn('item_sizes.id', array_keys($quantities))
            ->where('item_sizes.is_active', 1)
            ->where('items.is_active', 1)
            ->where('items.deleted_at', null)
            ->findAll();

        if (count($sizes) !== count($quantities)) {
            throw new InvalidArgumentException('One or more selected item sizes are no longer available.');
        }

        $lines = [];
        foreach ($sizes as $size) {
            $sizeId = (int) $size['id'];
            $quantity = $quantities[$sizeId];
            $unitPriceCents = $this->moneyToCents((string) $size['unit_price']);
            $lineTotalCents = $unitPriceCents * $quantity;
            $lines[] = [
                'item_id' => (int) $size['item_id'],
                'item_size_id' => $sizeId,
                'item_name' => (string) $size['item_name'],
                'size_name' => (string) $size['size_name'],
                'unit_price' => $this->format($unitPriceCents),
                'quantity' => $quantity,
                'line_total' => $this->format($lineTotalCents),
                'line_total_cents' => $lineTotalCents,
            ];
        }

        usort($lines, static fn (array $left, array $right): int => [$left['item_name'], $left['size_name']] <=> [$right['item_name'], $right['size_name']]);

        return $lines;
    }

    private function moneyToCents(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', trim($amount))) {
            throw new InvalidArgumentException('A configured service price is invalid.');
        }

        return (int) round((float) $amount * 100);
    }

    private function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
