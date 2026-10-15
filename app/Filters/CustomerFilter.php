<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class CustomerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $userId  = (int) $session->get('customer_user_id');
        $user    = $userId > 0 ? (new UserModel())->find($userId) : null;

        if ($user === null || $user['status'] !== 'active') {
            $session->remove(['customer_user_id', 'customer_user_name', 'customer_user_email', 'customer_is_logged_in']);
            if ($request->getMethod() === 'GET') {
                $intended = '/' . ltrim($request->getUri()->getPath(), '/');
                $query = $request->getUri()->getQuery();
                if ($query !== '') {
                    $intended .= '?' . $query;
                }
                $session->set('customer_intended_url', $intended);
            }

            $isBookingRequest = $request->getUri()->getPath() === '/account/bookings/new';

            return redirect()->route('login')->with(
                'error',
                $isBookingRequest
                    ? 'Log in with your Epic customer account to continue with your selected service.'
                    : 'Please log in with an active customer account.',
            );
        }

        if ($user['role'] !== 'customer') {
            $session->remove(['customer_user_id', 'customer_user_name', 'customer_user_email', 'customer_is_logged_in']);

            return redirect()->route('login')->with('error', 'Please log in with a customer account.');
        }

        $session->set([
            'customer_is_logged_in' => true,
            'customer_user_name' => $user['name'],
            'customer_user_email' => $user['email'],
        ]);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
