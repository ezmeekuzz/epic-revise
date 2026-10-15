<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\ItemModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;

final class DashboardController extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        $users = new UserModel();
        $bookings = new BookingModel();
        $payments = new PaymentModel();
        $transactions = new TransactionModel();

        return view('admin/dashboard', [
            'title'           => 'Dashboard',
            'activeAdminPage' => 'dashboard',
            'totalUsers'      => $users->countAllResults(),
            'activeCustomers' => $users->where('role', 'customer')->where('status', 'active')->countAllResults(),
            'admins'          => $users->where('role', 'admin')->countAllResults(),
            'recentUsers'     => $users->orderBy('created_at', 'DESC')->findAll(5),
            'totalBookings'   => $bookings->countAllResults(),
            'pendingBookings' => $bookings->where('status', 'pending')->countAllResults(),
            'activeItems'     => (new ItemModel())->where('is_active', 1)->countAllResults(),
            'completedRevenue' => (float) (($transactions->selectSum('amount')->where('status', 'completed')->first())['amount'] ?? 0),
            'pendingPayments' => $payments->where('status', 'pending')->countAllResults(),
            'recentBookings'  => $bookings->orderBy('created_at', 'DESC')->findAll(6),
        ]);
    }
}
