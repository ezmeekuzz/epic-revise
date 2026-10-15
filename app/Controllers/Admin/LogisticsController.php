<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

final class LogisticsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    /** @var list<string> */
    private array $statuses = ['unscheduled', 'scheduled', 'completed', 'cancelled'];

    public function index(): string
    {
        $leg = (string) $this->request->getGet('leg');
        $leg = in_array($leg, ['pickup', 'dropoff'], true) ? $leg : 'pickup';
        $status = (string) $this->request->getGet('status');
        $date = (string) $this->request->getGet('date');
        $model = (new BookingModel())->select('bookings.*, dorms.name AS dorm_name')->join('dorms', 'dorms.id = bookings.dorm_id', 'left');
        if (in_array($status, $this->statuses, true)) {
            $model->where($leg . '_status', $status);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $model->where($leg . '_date', $date);
        }
        $model->orderBy($leg . '_date', 'ASC')->orderBy($leg . '_time', 'ASC')->orderBy('created_at', 'DESC');

        return view('admin/logistics/index', ['title' => 'Pickup & Drop-off', 'activeAdminPage' => 'logistics', 'leg' => $leg, 'status' => $status, 'date' => $date, 'bookings' => $model->findAll(), 'statuses' => $this->statuses]);
    }

    public function update(int $id, string $leg): RedirectResponse
    {
        if (! in_array($leg, ['pickup', 'dropoff'], true)) {
            throw PageNotFoundException::forPageNotFound('Logistics leg not found.');
        }
        $booking = (new BookingModel())->find($id);
        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound('Booking not found.');
        }
        $status = (string) $this->request->getPost('status');
        if (! in_array($status, $this->statuses, true)) {
            return redirect()->back()->with('error', 'Choose a valid logistics status.');
        }
        if (in_array($status, ['scheduled', 'completed'], true) && empty($booking[$leg . '_date'])) {
            return redirect()->back()->with('error', 'Assign a ' . $leg . ' date before marking it ' . $status . '.');
        }
        (new BookingModel())->update($id, [$leg . '_status' => $status]);
        return redirect()->back()->with('success', ucfirst($leg) . ' status updated for ' . $booking['reference_code'] . '.');
    }
}
