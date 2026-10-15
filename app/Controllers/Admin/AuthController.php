<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuthService;
use CodeIgniter\HTTP\RedirectResponse;

final class AuthController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function login(): string
    {
        return view('admin/auth/login', [
            'title' => 'Administrator Login',
        ]);
    }

    public function authenticate(): RedirectResponse
    {
        $email    = mb_strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();
        $key      = 'admin_login_' . hash('sha256', $ip . '|' . $email);
        $ipKey    = 'admin_login_ip_' . hash('sha256', $ip);
        $throttler = service('throttler');

        if (! $throttler->check($key, 5, MINUTE * 15) || ! $throttler->check($ipKey, 50, MINUTE * 15)) {
            return redirect()->back()->withInput()->with('error', 'Too many administrator login attempts. Please wait 15 minutes and try again.');
        }

        if (! $this->validateData(['email' => $email, 'password' => $password], [
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|max_length[72]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $admin = (new AuthService())->attempt($email, $password, 'admin');
        if ($admin === null) {
            return redirect()->back()->withInput()->with('error', 'The administrator email or password is incorrect, or the account is inactive.');
        }

        $intended = (string) session()->get('admin_intended_url');
        session()->remove('admin_intended_url');
        $destination = $this->isAdminPath($intended)
            ? site_url(ltrim($intended, '/'))
            : site_url('admin');

        return redirect()->to($destination)->with('success', 'Welcome back, ' . $admin['name'] . '.');
    }

    public function logout(): RedirectResponse
    {
        (new AuthService())->logout('admin');

        return redirect()->route('admin.login')->with('success', 'You have been logged out of the admin portal securely.');
    }

    private function isAdminPath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '//')) {
            return false;
        }

        $routePath = (string) parse_url($path, PHP_URL_PATH);

        return ($routePath === '/admin' || str_starts_with($routePath, '/admin/'))
            && $routePath !== '/admin/login';
    }
}
