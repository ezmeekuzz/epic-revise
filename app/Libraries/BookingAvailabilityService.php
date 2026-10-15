<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingModel;
use DateTimeImmutable;
use DateTimeZone;

final class BookingAvailabilityService
{
    public function validationError(string $date, string $time, string $leg = 'pickup'): ?string
    {
        $leg   = $leg === 'dropoff' ? 'dropoff' : 'pickup';
        $label = $leg === 'dropoff' ? 'drop-off' : 'pickup';

        $settings = new SettingsService();
        if ($settings->get('bookings_enabled', '0') !== '1') {
            return $settings->get('booking_unavailable_message', 'Online booking is temporarily unavailable. Please contact our team for assistance.');
        }

        if ($date === '' && $time === '') {
            return null;
        }

        if ($date === '' || $time === '') {
            return 'Choose both a ' . $label . ' date and ' . $label . ' time, or leave both blank and provide them later.';
        }

        $timezone = new DateTimeZone($settings->get('business_timezone', 'America/New_York'));
        $scheduled = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $timezone);
        if ($scheduled === false || $scheduled->format('Y-m-d H:i') !== $date . ' ' . $time) {
            return 'Choose a valid ' . $label . ' date and time.';
        }

        $minimumAdvance = max(0, (int) $settings->get('minimum_advance_hours', '24'));
        if ($scheduled < (new DateTimeImmutable('now', $timezone))->modify('+' . $minimumAdvance . ' hours')) {
            return 'Choose a ' . $label . ' time at least ' . $minimumAdvance . ' hours from now.';
        }

        $blackoutDates = array_filter(array_map('trim', explode(',', $settings->get('blackout_dates'))));
        if (in_array($date, $blackoutDates, true)) {
            return 'That date is unavailable. Please choose another ' . $label . ' date.';
        }

        $start = $settings->get('pickup_start_time', '09:00');
        $end   = $settings->get('pickup_end_time', '17:00');
        if ($time < $start || $time >= $end) {
            return 'Choose a ' . $label . ' time between ' . date('g:i A', strtotime($start)) . ' and ' . date('g:i A', strtotime($end)) . '.';
        }

        $startMinutes = ((int) substr($start, 0, 2) * 60) + (int) substr($start, 3, 2);
        $timeMinutes  = ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
        $slotMinutes  = max(1, (int) $settings->get('slot_duration_minutes', '30'));
        if (($timeMinutes - $startMinutes) % $slotMinutes !== 0) {
            return 'Choose a time that matches the available ' . $slotMinutes . '-minute ' . $label . ' intervals.';
        }

        $capacity = max(1, (int) $settings->get('daily_booking_capacity', '25'));
        $booked   = (new BookingModel())->where($leg . '_date', $date)->where('status !=', 'cancelled')->countAllResults();
        if ($booked >= $capacity) {
            return 'That ' . $label . ' date has reached capacity. Please choose another date.';
        }

        return null;
    }
}
