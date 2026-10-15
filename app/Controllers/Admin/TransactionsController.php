<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TransactionModel;

final class TransactionsController extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $type   = (string) $this->request->getGet('type');
        $model  = new TransactionModel();
        $model->select('transactions.*, bookings.reference_code AS booking_reference, bookings.customer_name, payments.reference AS payment_reference')
            ->join('bookings', 'bookings.id = transactions.booking_id')
            ->join('payments', 'payments.id = transactions.payment_id', 'left');
        if ($search !== '') {
            $model->groupStart()->like('transactions.reference', $search)->orLike('bookings.reference_code', $search)->orLike('bookings.customer_name', $search)->groupEnd();
        }
        if (in_array($type, ['payment', 'refund', 'adjustment'], true)) {
            $model->where('transactions.type', $type);
        }

        return view('admin/transactions/index', [
            'title' => 'Transaction Ledger', 'activeAdminPage' => 'transactions',
            'transactions' => $model->orderBy('transactions.created_at', 'DESC')->findAll(),
            'filters' => ['q' => $search, 'type' => $type],
        ]);
    }
}
