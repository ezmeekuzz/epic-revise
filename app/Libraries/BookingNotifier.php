<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use App\Models\ItemSizeModel;
use App\Models\TransactionModel;
use Throwable;

/**
 * Sends the customer and admin emails for booking creation and for pickup/returning to campus
 * schedule changes. Booking saves never fail because an email could not be sent.
 */
final class BookingNotifier
{
    public function __construct()
    {
        helper('url');
    }

    /** Admin alert for a new booking (the address in Settings > Support email). */
    public function adminBookingCreated(int $bookingId): void
    {
        $this->guard('admin booking created', $bookingId, function () use ($bookingId): void {
            $recipient = $this->adminEmail();
            $booking   = (new BookingModel())->find($bookingId);
            if ($recipient === '' || $booking === null) {
                return;
            }

            (new SmtpService())->sendAdminBookingCreated(
                $recipient,
                $booking,
                (new BookingItemModel())->where('booking_id', $bookingId)->findAll(),
                $this->dorm($booking),
                url_to('admin.bookings.show', $bookingId),
            );
        });
    }

    /** Customer copy for a booking created from a logged-in account. */
    public function customerBookingCreated(int $bookingId): void
    {
        $this->guard('customer booking created', $bookingId, function () use ($bookingId): void {
            $booking = (new BookingModel())->find($bookingId);
            if ($booking === null) {
                return;
            }

            $link = (new BookingLinkService())->accountLink($booking, '/account/bookings/' . $bookingId);
            (new SmtpService())->sendBookingCreated(
                $booking,
                (new BookingItemModel())->where('booking_id', $bookingId)->findAll(),
                $this->dorm($booking),
                $link['url'],
            );
        });
    }

    /** Receipt to the student (copying parent and support) once an online payment is verified. */
    public function paymentReceived(int $bookingId, float $amount): void
    {
        $this->guard('payment received', $bookingId, function () use ($bookingId, $amount): void {
            $booking = (new BookingModel())->find($bookingId);
            if ($booking === null) {
                return;
            }

            $row = (new \App\Models\TransactionModel())->selectSum('amount')->where('booking_id', $bookingId)->where('status', 'completed')->first();
            $balance = max(0, round((float) $booking['total_amount'] - round((float) ($row['amount'] ?? 0), 2), 2));
            (new SmtpService())->sendPaymentReceived(
                $booking,
                (new BookingItemModel())->where('booking_id', $bookingId)->findAll(),
                $this->dorm($booking),
                $amount,
                $balance,
                (new BookingLinkService())->accountLink($booking, '/account/bookings/' . $bookingId),
            );
        });
    }

    /**
     * Confirms items/boxes added to an existing order (student, parent and support).
     *
     * @param list<array{size_id: int, quantity: int}> $items
     */
    public function itemsAdded(int $bookingId, int $boxes, array $items): void
    {
        $this->guard('order items added', $bookingId, function () use ($bookingId, $boxes, $items): void {
            $booking = (new BookingModel())->find($bookingId);
            if ($booking === null) {
                return;
            }

            $rows = [];
            if ($boxes > 0) {
                $rows[] = ['Additional boxes', (string) $boxes . ' @ $' . number_format((float) $booking['additional_box_amount'], 2)];
            }
            foreach ($items as $item) {
                $size = (new ItemSizeModel())->select('item_sizes.name AS size_name, items.name AS item_name')->join('items', 'items.id = item_sizes.item_id')->find((int) $item['size_id']);
                if ($size !== null) {
                    $rows[] = [$size['item_name'] . ($size['size_name'] ? ' (' . $size['size_name'] . ')' : ''), 'x ' . (int) $item['quantity']];
                }
            }

            $paid    = (float) ((new TransactionModel())->selectSum('amount')->where('booking_id', $bookingId)->where('status', 'completed')->first()['amount'] ?? 0);
            $balance = max(0, round((float) $booking['total_amount'] - $paid, 2));
            $links   = new BookingLinkService();
            $link    = $links->accountLink($booking, '/account/bookings/' . $bookingId);
            (new SmtpService())->sendOrderItemsAdded(
                $booking,
                (new BookingItemModel())->where('booking_id', $bookingId)->findAll(),
                $this->dorm($booking),
                $rows,
                $balance,
                $link['url'],
                $balance > 0 ? $links->paymentUrl($booking) : null,
            );
        });
    }

    /**
     * Emails the customer and the admin when a leaving campus or returning to campus/time differs from
     * $before. Does nothing when neither changed.
     *
     * @param array<string, mixed> $before Booking row as it was before the update.
     */
    public function scheduleChanged(array $before, int $bookingId): void
    {
        $booking = (new BookingModel())->find($bookingId);
        if ($booking === null) {
            return;
        }

        $changes = [];
        foreach (['pickup', 'dropoff'] as $leg) {
            if ($this->slot($before, $leg) !== $this->slot($booking, $leg)) {
                $changes[] = $leg;
            }
        }
        if ($changes === []) {
            return;
        }

        $smtp = new SmtpService();
        $dorm = $this->dorm($booking);

        $this->guard('schedule updated (customer)', $bookingId, function () use ($smtp, $booking, $before, $dorm, $changes, $bookingId): void {
            $link = (new BookingLinkService())->accountLink($booking, '/account/bookings/' . $bookingId);
            $smtp->sendScheduleUpdated((string) $booking['customer_email'], (string) $booking['customer_name'], false, $booking, $before, $dorm, $changes, $link['url'], $link['label']);
        });

        $this->guard('schedule updated (admin)', $bookingId, function () use ($smtp, $booking, $before, $dorm, $changes, $bookingId): void {
            $recipient = $this->adminEmail();
            if ($recipient !== '') {
                $smtp->sendScheduleUpdated($recipient, 'EPIC Team', true, $booking, $before, $dorm, $changes, url_to('admin.bookings.show', $bookingId), 'Open booking in admin');
            }
        });
    }

    /** @param array<string, mixed> $booking */
    private function slot(array $booking, string $leg): string
    {
        $date = (string) ($booking[$leg . '_date'] ?? '');
        $time = (string) ($booking[$leg . '_time'] ?? '');

        return $date . '|' . substr($time, 0, 5);
    }

    /** @param array<string, mixed> $booking */
    private function dorm(array $booking): ?array
    {
        return ! empty($booking['dorm_id']) ? (new DormModel())->find((int) $booking['dorm_id']) : null;
    }

    private function adminEmail(): string
    {
        return (new SettingsService())->get('support_email');
    }

    private function guard(string $label, int $bookingId, callable $send): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            log_message('error', 'Booking email "{label}" for booking {booking} failed: {type}', ['label' => $label, 'booking' => $bookingId, 'type' => $exception::class]);
        }
    }
}
