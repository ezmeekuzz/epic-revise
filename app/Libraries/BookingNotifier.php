<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use Throwable;

/**
 * Sends the customer and admin emails for booking creation and for pickup/drop-off
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

    /**
     * Emails the customer and the admin when a pickup or drop-off date/time differs from
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
