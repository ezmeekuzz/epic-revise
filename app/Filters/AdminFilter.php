<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $userId  = (int) $session->get('admin_user_id');
        $user    = $userId > 0 ? (new UserModel())->find($userId) : null;

        if ($user === null || $user['status'] !== 'active') {
            $session->remove(['admin_user_id', 'admin_user_name', 'admin_user_email', 'admin_is_logged_in', 'admin_reauth_user_id', 'admin_reauth_at']);

            if ($request->getMethod() === 'GET') {
                $intended = '/' . ltrim($request->getUri()->getPath(), '/');
                $query = $request->getUri()->getQuery();
                if ($query !== '') {
                    $intended .= '?' . $query;
                }
                $session->set('admin_intended_url', $intended);
            }

            return redirect()->route('admin.login')->with('error', 'Please log in with an administrator account.');
        }

        if ($user['role'] !== 'admin') {
            $session->remove(['admin_user_id', 'admin_user_name', 'admin_user_email', 'admin_is_logged_in', 'admin_reauth_user_id', 'admin_reauth_at']);

            return redirect()->route('admin.login')->with('error', 'Please log in with an administrator account.');
        }

        $session->set([
            'admin_is_logged_in' => true,
            'admin_user_name'    => $user['name'],
            'admin_user_email'   => $user['email'],
        ]);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
