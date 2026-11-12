<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ActivityLog;
use App\Libraries\AuthService;
use App\Libraries\Totp;
use App\Models\UserModel;
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
            return redirect()->back()->withInput()->with('alert', ['type' => 'warning', 'title' => 'Too many attempts', 'message' => 'Too many administrator login attempts. Please wait 15 minutes and try again.']);
        }

        if (! $this->validateData(['email' => $email, 'password' => $password], [
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|max_length[72]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $auth  = new AuthService();
        $admin = $auth->attempt($email, $password, 'admin', false);
        if ($admin === null) {
            ActivityLog::record('login.failed', $email, null, null, 'Unknown');

            return redirect()->back()->withInput()->with('error', 'The administrator email or password is incorrect, the account is inactive, or it is temporarily locked after too many failed attempts.');
        }

        if ((int) ($admin['totp_enabled'] ?? 0) === 1) {
            session()->set(['admin_2fa_user_id' => (int) $admin['id'], 'admin_2fa_at' => time()]);

            return redirect()->route('admin.login.verify');
        }

        $auth->completeLogin($admin);
        ActivityLog::record('login.success', $admin['email'], null, (int) $admin['id'], (string) $admin['name']);

        return $this->finishLogin($admin);
    }

    public function codeForm(): string|RedirectResponse
    {
        if ($this->pendingTwoFactorUser() === null) {
            return redirect()->route('admin.login');
        }

        return view('admin/auth/verify_code', ['title' => 'Two-factor login']);
    }

    public function verifyCode(): RedirectResponse
    {
        $user = $this->pendingTwoFactorUser();
        if ($user === null) {
            return redirect()->route('admin.login')->with('error', 'Your sign-in expired. Please start again.');
        }

        $key = 'admin_2fa_' . (int) $user['id'];
        if (! service('throttler')->check($key, 5, MINUTE * 15)) {
            session()->remove(['admin_2fa_user_id', 'admin_2fa_at']);

            return redirect()->route('admin.login')->with('alert', ['type' => 'warning', 'title' => 'Too many attempts', 'message' => 'Too many incorrect codes. Please wait 15 minutes and sign in again.']);
        }

        if (! (new Totp())->verify(Totp::decryptSecret((string) $user['totp_secret']), (string) $this->request->getPost('code'))) {
            ActivityLog::record('login.2fa_failed', $user['email'], null, (int) $user['id'], (string) $user['name']);

            return redirect()->back()->with('error', 'That code was not correct. Please try again.');
        }

        session()->remove(['admin_2fa_user_id', 'admin_2fa_at']);
        (new AuthService())->completeLogin($user);
        ActivityLog::record('login.success', $user['email'], 'two-factor', (int) $user['id'], (string) $user['name']);

        return $this->finishLogin($user);
    }

    /** @param array<string, mixed> $admin */
    private function finishLogin(array $admin): RedirectResponse
    {
        $intended = (string) session()->get('admin_intended_url');
        session()->remove('admin_intended_url');
        $destination = $this->isAdminPath($intended)
            ? site_url(ltrim($intended, '/'))
            : site_url('admin');

        return redirect()->to($destination)->with('success', 'Welcome back, ' . $admin['name'] . '.');
    }

    /** @return array<string, mixed>|null */
    private function pendingTwoFactorUser(): ?array
    {
        $id = (int) session()->get('admin_2fa_user_id');
        if ($id < 1 || (int) session()->get('admin_2fa_at') < time() - 300) {
            return null;
        }
        $user = (new UserModel())->find($id);

        return $user !== null && $user['role'] === 'admin' && $user['status'] === 'active' && (int) $user['totp_enabled'] === 1 ? $user : null;
    }

    public function logout(): RedirectResponse
    {
        ActivityLog::record('logout');
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
