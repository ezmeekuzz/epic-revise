<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

final class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        $path = rtrim('/' . ltrim($request->getUri()->getPath(), '/'), '/') ?: '/';
        $isAdminPortal = $path === '/admin/login';

        if ($isAdminPortal) {
            return $session->get('admin_is_logged_in') && (int) $session->get('admin_user_id') > 0
                ? redirect()->route('admin.dashboard')
                : null;
        }

        return $session->get('customer_is_logged_in') && (int) $session->get('customer_user_id') > 0
            ? redirect()->route('account')
            : null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
