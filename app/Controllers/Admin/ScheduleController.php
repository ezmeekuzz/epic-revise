<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use DateTimeZone;

final class ScheduleController extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string|RedirectResponse
    {
        $month = (string) $this->request->getGet('month');
        if ($month === '') {
            $month = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m');
        }
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return redirect()->route('admin.schedule')->with('error', 'Choose a valid calendar month.');
        }

        $first = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01', new DateTimeZone('UTC'));
        if ($first === false) {
            return redirect()->route('admin.schedule')->with('error', 'Choose a valid calendar month.');
        }
        $last = $first->modify('last day of this month');
        $bookings = (new BookingModel())
            ->select('bookings.*, dorms.name AS dorm_name')
            ->join('dorms', 'dorms.id = bookings.dorm_id', 'left')
            ->groupStart()
                ->where('pickup_date >=', $first->format('Y-m-d'))->where('pickup_date <=', $last->format('Y-m-d'))
                ->orGroupStart()->where('dropoff_date >=', $first->format('Y-m-d'))->where('dropoff_date <=', $last->format('Y-m-d'))->groupEnd()
            ->groupEnd()->orderBy('pickup_date', 'ASC')->orderBy('pickup_time', 'ASC')->findAll();

        $weeks = [];
        $cursor = $first->modify('monday this week');
        $end = $last->modify('sunday this week');
        while ($cursor <= $end) {
            $week = [];
            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->modify('+' . $day . ' days');
                $key = $date->format('Y-m-d');
                $week[] = ['date' => $key, 'day' => (int) $date->format('j'), 'inMonth' => $date->format('Y-m') === $month, 'events' => array_values(array_filter($bookings, static fn (array $booking): bool => ($booking['pickup_date'] ?? '') === $key || ($booking['dropoff_date'] ?? '') === $key))];
            }
            $weeks[] = $week;
            $cursor = $cursor->modify('+7 days');
        }

        return view('admin/schedule/index', ['title' => 'Calendar Schedule', 'activeAdminPage' => 'schedule', 'month' => $month, 'monthLabel' => $first->format('F Y'), 'weeks' => $weeks, 'bookings' => $bookings, 'previousMonth' => $first->modify('-1 month')->format('Y-m'), 'nextMonth' => $first->modify('+1 month')->format('Y-m')]);
    }
}
