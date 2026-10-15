<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\PaymentService;
use App\Libraries\SettingsService;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;
use Throwable;

final class PaymentsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $model  = new PaymentModel();
        $model->select('payments.*, bookings.reference_code AS booking_reference, bookings.customer_name')->join('bookings', 'bookings.id = payments.booking_id');
        if ($search !== '') {
            $model->groupStart()->like('payments.reference', $search)->orLike('bookings.reference_code', $search)->orLike('bookings.customer_name', $search)->groupEnd();
        }
        if (in_array($status, ['pending', 'completed', 'failed', 'refunded'], true)) {
            $model->where('payments.status', $status);
        }

        return view('admin/payments/index', [
            'title' => 'Payments', 'activeAdminPage' => 'payments',
            'payments' => $model->orderBy('payments.created_at', 'DESC')->findAll(),
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function new(): string
    {
        return view('admin/payments/form', [
            'title' => 'Record Payment', 'activeAdminPage' => 'payments',
            'bookings' => (new BookingModel())->whereNotIn('status', ['cancelled'])->orderBy('created_at', 'DESC')->findAll(100),
            'defaultCurrency' => (new SettingsService())->get('payment_currency', 'USD'),
            'selectedBookingId' => (int) $this->request->getGet('booking'),
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = [
            'booking_id' => (string) $this->request->getPost('booking_id'), 'provider' => (string) $this->request->getPost('provider'),
            'method' => trim((string) $this->request->getPost('method')), 'amount' => (string) $this->request->getPost('amount'),
            'currency' => strtoupper(trim((string) $this->request->getPost('currency'))), 'status' => (string) $this->request->getPost('status'),
            'external_reference' => trim((string) $this->request->getPost('external_reference')), 'notes' => trim((string) $this->request->getPost('notes')),
        ];
        $rules = [
            'booking_id' => 'required|is_natural_no_zero', 'provider' => 'required|in_list[manual,stripe,square,paypal,other]',
            'method' => 'required|max_length[60]', 'amount' => 'required|decimal|greater_than[0]',
            'currency' => 'required|alpha|exact_length[3]', 'status' => 'required|in_list[pending,completed,failed]',
            'external_reference' => 'permit_empty|max_length[150]', 'notes' => 'permit_empty|max_length[5000]',
        ];
        if (! $this->validateData($data, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $booking = (new BookingModel())->find((int) $data['booking_id']);
        if ($booking === null) {
            return redirect()->back()->withInput()->with('error', 'The selected booking does not exist.');
        }

        $data['user_id'] = $booking['user_id'];
        $data['amount'] = number_format((float) $data['amount'], 2, '.', '');
        $data['reference'] = 'PAY-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $data['paid_at'] = $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        try {
            (new PaymentService())->create($data);
        } catch (RuntimeException $exception) {
            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Unable to record payment: {type}', ['type' => $exception::class]);
            return redirect()->back()->withInput()->with('error', 'The payment could not be recorded. No completed transaction was confirmed.');
        }

        return redirect()->route('admin.payments')->with('success', 'Payment ' . $data['reference'] . ' was recorded.');
    }

    public function complete(int $id): RedirectResponse
    {
        try {
            (new PaymentService())->complete($id);
            return redirect()->route('admin.payments')->with('success', 'Pending payment completed and posted to the transaction ledger.');
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.payments')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Unable to complete payment: {type}', ['type' => $exception::class]);
            return redirect()->route('admin.payments')->with('error', 'The payment could not be completed. No completed transaction was confirmed.');
        }
    }

    public function refund(int $id): RedirectResponse
    {
        try {
            (new PaymentService())->refund($id);
            return redirect()->route('admin.payments')->with('success', 'Refund recorded in the immutable transaction ledger.');
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.payments')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            log_message('error', 'Unable to refund payment: {type}', ['type' => $exception::class]);
            return redirect()->route('admin.payments')->with('error', 'The refund could not be recorded. Check the transaction ledger before retrying.');
        }
    }
}
