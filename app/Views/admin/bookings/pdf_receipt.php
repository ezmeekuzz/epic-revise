<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $booking
 * @var array<string, mixed>|null $dorm
 * @var list<array<string, mixed>> $bookingItems
 * @var float $paid
 * @var float $balance
 * @var string|null $logoDataUri
 */
$isStorage = $booking['booking_type'] === 'summer_storage';
$itemTotal = array_sum(array_map(static fn (array $line): float => (float) $line['line_total'], $bookingItems));
$brandName = 'EPIC Storage Solutions';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($booking['reference_code']) ?> receipt</title>
<style>
    body { font-family: Helvetica, Arial, sans-serif; color: #141414; font-size: 12px; margin: 0; }
    .header { background: #083150; padding: 18px 28px; }
    .header img { height: 38px; }
    .content { padding: 24px 28px; }
    .eyebrow { color: #005aab; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: bold; margin: 0 0 4px; }
    h1 { font-size: 20px; margin: 0 0 6px; color: #083150; }
    .meta { color: #555; margin: 0 0 18px; }
    .status-pill { display: inline-block; padding: 2px 10px; border-radius: 10px; background: #eef4fb; color: #083150; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th, td { border: 1px solid #dedede; padding: 6px 8px; text-align: left; font-size: 11px; vertical-align: top; }
    th { background: #f0f0f0; }
    .section-title { background: #083150; color: #fff; }
    .totals td { border: none; padding: 3px 8px; }
    .totals .label { text-align: right; width: 70%; }
    .totals .final td { font-weight: bold; border-top: 2px solid #083150; }
    .notes { margin-top: 12px; }
</style>
</head>
<body>
    <div class="header">
        <?php if ($logoDataUri): ?><img src="<?= esc($logoDataUri, 'attr') ?>" alt="<?= esc($brandName, 'attr') ?>"><?php endif ?>
    </div>
    <div class="content">
        <p class="eyebrow"><?= $isStorage ? 'Summer Storage' : 'Summer Advantage' ?> booking receipt</p>
        <h1><?= esc($booking['reference_code']) ?></h1>
        <p class="meta">Created <?= esc(date('F j, Y g:i A', strtotime((string) $booking['created_at']))) ?> &middot; <span class="status-pill"><?= esc(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></p>

        <table>
            <tr><th class="section-title" colspan="2">Customer</th></tr>
            <tr><td>Name</td><td><?= esc($booking['customer_name']) ?></td></tr>
            <tr><td>Email</td><td><?= esc($booking['customer_email']) ?></td></tr>
            <tr><td>Phone / Student ID</td><td><?= esc($booking['customer_phone'] ?: 'Not provided') ?> / <?= esc($booking['student_id'] ?: 'Not provided') ?></td></tr>
        </table>

        <table>
            <tr><th class="section-title" colspan="2">Pickup &amp; drop-off</th></tr>
            <tr><td>Pickup</td><td><?= $booking['pickup_date'] ? esc(date('F j, Y', strtotime((string) $booking['pickup_date']))) : 'Not scheduled' ?><?= $booking['pickup_time'] ? ' at ' . esc(date('g:i A', strtotime((string) $booking['pickup_time']))) : '' ?></td></tr>
            <tr><td>Pickup location</td><td><?= esc($dorm['name'] ?? ($booking['pickup_location'] ?: 'Not provided')) ?><?= $booking['room_number'] ? ' - Room ' . esc($booking['room_number']) : '' ?></td></tr>
            <tr><td>Drop-off</td><td><?= $booking['dropoff_date'] ? esc(date('F j, Y', strtotime((string) $booking['dropoff_date']))) : 'Not scheduled' ?><?= $booking['dropoff_time'] ? ' at ' . esc(date('g:i A', strtotime((string) $booking['dropoff_time']))) : '' ?></td></tr>
            <tr><td>Drop-off location</td><td><?= esc($booking['dropoff_location'] ?: 'Not provided') ?><?= $booking['dropoff_room_number'] ? ' - Room ' . esc($booking['dropoff_room_number']) : '' ?></td></tr>
            <tr><td>Warehouse row</td><td><?= $booking['row_in_warehouse'] !== null ? esc((string) $booking['row_in_warehouse']) : 'Not assigned' ?></td></tr>
        </table>

        <?php if ($bookingItems !== []): ?>
        <table>
            <tr><th class="section-title" colspan="5">Booking items</th></tr>
            <tr><th>Item</th><th>Size</th><th>Unit price</th><th>Qty</th><th>Line total</th></tr>
            <?php foreach ($bookingItems as $line): ?>
            <tr><td><?= esc($line['item_name']) ?></td><td><?= esc($line['size_name'] ?: 'Standard') ?></td><td>$<?= number_format((float) $line['unit_price'], 2) ?></td><td><?= number_format((int) $line['quantity']) ?></td><td>$<?= number_format((float) $line['line_total'], 2) ?></td></tr>
            <?php endforeach ?>
        </table>
        <?php endif ?>

        <table class="totals">
            <?php if ($isStorage): ?>
            <tr><td class="label">Base price</td><td>$<?= number_format((float) $booking['base_price'], 2) ?></td></tr>
            <tr><td class="label">Selected items</td><td>$<?= number_format($itemTotal, 2) ?></td></tr>
            <?php endif ?>
            <tr><td class="label"><?= $isStorage ? 'Additional boxes' : 'Boxes' ?></td><td>$<?= number_format((float) $booking['addtl_box_total_amount'], 2) ?></td></tr>
            <tr><td class="label">Subtotal</td><td>$<?= number_format((float) $booking['subtotal'], 2) ?></td></tr>
            <tr><td class="label">Discount</td><td>-$<?= number_format((float) $booking['discount_amount'], 2) ?></td></tr>
            <tr class="final"><td class="label">Total</td><td>$<?= number_format((float) $booking['total_amount'], 2) ?></td></tr>
            <tr><td class="label">Paid</td><td>$<?= number_format($paid, 2) ?></td></tr>
            <tr><td class="label">Balance due</td><td>$<?= number_format($balance, 2) ?></td></tr>
        </table>

        <?php if ($booking['admin_notes']): ?><p class="notes"><strong>Internal notes:</strong><br><?= nl2br(esc($booking['admin_notes'])) ?></p><?php endif ?>
    </div>
</body>
</html>
