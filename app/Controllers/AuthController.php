<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AuthService;
use App\Libraries\SmtpService;
use App\Models\EmailVerificationTokenModel;
use App\Models\MagicLoginTokenModel;
use App\Models\PasswordResetTokenModel;
use App\Models\UserModel;
use App\Libraries\CustomerDetails;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use Throwable;

final class AuthController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function login(): string
    {
        return $this->authView('auth/login', 'Customer Log In | Epic Storage Solutions', 'Securely access your Epic Storage Solutions customer account.');
    }

    public function authenticate(): RedirectResponse
    {
        $email    = mb_strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();
        $key      = 'customer_login_' . hash('sha256', $ip . '|' . $email);
        $ipKey    = 'customer_login_ip_' . hash('sha256', $ip);

        $throttler = service('throttler');
        if (! $throttler->check($key, 5, MINUTE * 15) || ! $throttler->check($ipKey, 50, MINUTE * 15)) {
            return redirect()->back()->withInput()->with('error', 'Too many login attempts. Please wait 15 minutes and try again.');
        }

        if (! $this->validateData(['email' => $email, 'password' => $password], [
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|max_length[72]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = (new AuthService())->attempt($email, $password, 'customer');
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'The customer email or password is incorrect, or the account is inactive.');
        }

        $intended = (string) session()->get('customer_intended_url');
        session()->remove('customer_intended_url');
        $destination = site_url('account');

        if ($this->isCustomerPath($intended)) {
            $destination = site_url(ltrim($intended, '/'));
        }

        return redirect()->to($destination)->with('success', 'Welcome back, ' . $user['name'] . '.');
    }

    public function register(): string
    {
        return $this->authView('auth/register', 'Create an Account | Epic Storage Solutions', 'Create your customer account for Epic Storage Solutions.');
    }

    public function store(): RedirectResponse
    {
        $data = [
            'name'             => trim((string) $this->request->getPost('name')),
            'email'            => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
            'terms'            => (string) $this->request->getPost('terms'),
        ];
        $data = [...$data, ...CustomerDetails::input($this->request)];
        $rules = [
            ...CustomerDetails::rules(),
            'name'             => 'required|min_length[2]|max_length[100]',
            'email'            => 'required|valid_email|max_length[254]|is_unique[users.email]',
            'password'         => 'required|min_length[12]|max_length[72]',
            'password_confirm' => 'required|matches[password]',
            'terms'            => 'required|in_list[1]',
        ];
        $messages = [
            'email' => ['is_unique' => 'An account already exists for this email address.'],
            'terms' => ['required' => 'You must agree to the Terms and Conditions.', 'in_list' => 'You must agree to the Terms and Conditions.'],
        ];

        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $redirectPath = $this->safeRedirectPath((string) session()->get('customer_intended_url')) ?? '/services';

        try {
            $userId = $users->insert([
                ...CustomerDetails::input($this->request),
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'          => 'customer',
                'status'        => 'pending',
                'email_verified_at' => null,
            ], true);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'We could not create that account. The email may already be registered.');
        }

        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->route('login')->with('error', 'Your account could not be loaded. Please try again.');
        }

        $rawToken = bin2hex(random_bytes(32));
        try {
            (new EmailVerificationTokenModel())->insert([
                'user_id'       => (int) $user['id'],
                'token_hash'    => hash('sha256', $rawToken),
                'redirect_path' => $redirectPath,
                'expires_at'    => (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (DatabaseException) {
            session()->remove('customer_intended_url');

            return $this->verificationRedirect([
                'verification_email' => $data['email'],
                'verification_heading' => 'Your account was created',
                'verification_message' => 'We could not prepare the confirmation link yet. Request another email below and we will try again.',
                'verification_error' => 'The confirmation email could not be prepared. Please request a new link.',
                'verification_resend' => true,
            ]);
        }

        session()->remove('customer_intended_url');
        $serviceLabel = $this->serviceLabel($redirectPath);
        $emailSent = false;
        try {
            $emailSent = (new SmtpService())->sendVerification(
                $data['email'],
                $data['name'],
                site_url('verify-email?token=' . rawurlencode($rawToken)),
                $serviceLabel,
            );
        } catch (Throwable $exception) {
            log_message('error', 'Account verification email failed ({type}).', ['type' => $exception::class]);
        }

        if (! $emailSent) {
            return $this->verificationRedirect([
                'verification_email' => $data['email'],
                'verification_heading' => 'Your account was created',
                'verification_message' => 'Your account is waiting for email confirmation. Use the form below to resend the link after SMTP delivery is configured.',
                'verification_error' => 'We could not send the confirmation email. Your account was not logged in or activated.',
                'verification_resend' => true,
            ]);
        }

        return $this->verificationRedirect([
            'verification_email' => $data['email'],
            'verification_heading' => 'Check your inbox',
            'verification_message' => 'The mail server accepted a confirmation message for your email address. Check your inbox, spam, and quarantine folders, then click the link to activate your account and continue your selected service.',
            'verification_resend' => true,
        ]);
    }

    public function verifyEmail(): RedirectResponse|string
    {
        $rawToken = strtolower(trim((string) $this->request->getGet('token')));
        if ($rawToken === '') {
            return $this->verificationNotice();
        }

        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return $this->verificationResult('Invalid confirmation link', 'This confirmation link is not valid. Request a fresh link below.', true);
        }

        $tokens = new EmailVerificationTokenModel();
        $token = $tokens->where('token_hash', hash('sha256', $rawToken))->where('used_at', null)->first();
        if ($token === null || strtotime((string) $token['expires_at']) < time()) {
            return $this->verificationResult('Confirmation link expired', 'This confirmation link is missing, expired, or has already been used. Request a new link below.', true);
        }

        $users = new UserModel();
        $user = $users->find((int) $token['user_id']);
        if ($user === null || $user['role'] !== 'customer') {
            return $this->verificationResult('Account unavailable', 'This confirmation link is no longer associated with an active customer account.', false);
        }

        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transBegin();
        $claimed = $tokens->builder()->where('id', (int) $token['id'])->where('used_at', null)->update(['used_at' => $now]);
        if (! $claimed || $db->affectedRows() !== 1) {
            $db->transRollback();

            return $this->verificationResult('Confirmation link expired', 'This confirmation link is missing, expired, or has already been used. Request a new link below.', true);
        }

        $updated = $users->update((int) $user['id'], ['status' => 'active', 'email_verified_at' => $now]);
        if (! $updated) {
            $db->transRollback();

            return $this->verificationResult('Confirmation failed', 'We could not activate this account. Please request a fresh confirmation link.', true);
        }
        $tokens->invalidateForUser((int) $user['id'], (int) $token['id']);

        if (! $db->transStatus()) {
            $db->transRollback();

            return $this->verificationResult('Confirmation failed', 'We could not activate this account. Please request a fresh confirmation link.', true);
        }
        $db->transCommit();

        $user = $users->find((int) $user['id']);
        if ($user === null) {
            return $this->verificationResult('Confirmation failed', 'Your account could not be loaded after confirmation. Please contact support.', false);
        }

        (new AuthService())->startSession($user);
        $redirectPath = $this->safeRedirectPath((string) ($token['redirect_path'] ?? '')) ?? '/account';

        return redirect()->to(site_url(ltrim($redirectPath, '/')))->with('success', 'Your email is confirmed. Welcome to Epic, ' . $user['name'] . '.');
    }

    public function resendVerification(): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $this->request->getPost('email')));
        $resendKey = 'verify_resend_' . hash('sha256', $this->request->getIPAddress() . '|' . $email);
        if (! service('throttler')->check($resendKey, 3, MINUTE * 30)) {
            return $this->verificationRedirect([
                'verification_email' => $email,
                'verification_heading' => 'Check your inbox',
                'verification_message' => 'Please wait before requesting another confirmation email.',
                'verification_error' => 'Too many requests. Please try again in 30 minutes.',
                'verification_resend' => true,
            ]);
        }

        if (! $this->validateData(['email' => $email], ['email' => 'required|valid_email|max_length[254]'])) {
            return $this->verificationRedirect([
                'verification_heading' => 'Check your inbox',
                'verification_message' => 'Enter a valid email address to request another confirmation link.',
                'verification_error' => 'Please enter a valid email address.',
                'verification_resend' => true,
            ]);
        }

        $user = (new UserModel())->where('email', $email)->where('role', 'customer')->first();
        $sent = false;
        if ($user !== null && ($user['status'] ?? '') !== 'active' && empty($user['email_verified_at'])) {
            $tokens = new EmailVerificationTokenModel();
            try {
                $latest = $tokens->where('user_id', (int) $user['id'])->orderBy('created_at', 'DESC')->first();
                $redirectPath = $this->safeRedirectPath((string) ($latest['redirect_path'] ?? '')) ?? '/services';
                $now = date('Y-m-d H:i:s');
                $tokens->invalidateForUser((int) $user['id']);
                $rawToken = bin2hex(random_bytes(32));
                $tokens->insert([
                    'user_id' => (int) $user['id'], 'token_hash' => hash('sha256', $rawToken),
                    'redirect_path' => $redirectPath, 'expires_at' => (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s'), 'created_at' => $now,
                ]);

                try {
                    $sent = (new SmtpService())->sendVerification($email, (string) $user['name'], site_url('verify-email?token=' . rawurlencode($rawToken)), $this->serviceLabel($redirectPath));
                } catch (Throwable $exception) {
                    log_message('error', 'Verification resend failed ({type}).', ['type' => $exception::class]);
                }
            } catch (DatabaseException $exception) {
                log_message('error', 'Verification token regeneration failed ({type}).', ['type' => $exception::class]);
            }
        }

        return $this->verificationRedirect([
            'verification_email' => $email,
            'verification_heading' => $sent ? 'Confirmation email sent' : 'Check your inbox',
            'verification_message' => $sent ? 'If an unconfirmed Epic customer account exists for that address, the mail server accepted a fresh confirmation message. Check spam or quarantine if it is not visible.' : 'If an unconfirmed Epic customer account exists for that address, we will send a fresh confirmation link after SMTP delivery is available.',
            'verification_error' => $sent ? null : 'The confirmation email could not be sent right now. Please check the SMTP settings and try again.',
            'verification_resend' => true,
        ]);
    }

    public function logout(): RedirectResponse
    {
        (new AuthService())->logout('customer');

        return redirect()->route('login')->with('success', 'You have been logged out of your customer account securely.');
    }

    public function forgotPassword(): string
    {
        return $this->authView('auth/forgot_password', 'Forgot Password | Epic Storage Solutions', 'Reset your Epic Storage Solutions customer account password.');
    }

    public function sendPasswordReset(): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $this->request->getPost('email')));
        $key = 'forgot_password_' . hash('sha256', $this->request->getIPAddress() . '|' . $email);
        if (! service('throttler')->check($key, 3, MINUTE * 30)) {
            return redirect()->route('forgot.password')->with('error', 'Too many requests. Please wait 30 minutes and try again.');
        }

        if (! $this->validateData(['email' => $email], ['email' => 'required|valid_email|max_length[254]'])) {
            return redirect()->route('forgot.password')->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = (new UserModel())->where('email', $email)->where('role', 'customer')->where('status', 'active')->first();
        if ($user !== null) {
            try {
                $tokens = new PasswordResetTokenModel();
                $tokens->invalidateForUser((int) $user['id']);
                $rawToken = bin2hex(random_bytes(32));
                $tokens->insert([
                    'user_id'    => (int) $user['id'],
                    'token_hash' => hash('sha256', $rawToken),
                    'expires_at' => (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                (new SmtpService())->sendPasswordReset($email, (string) $user['name'], site_url('reset-password?token=' . rawurlencode($rawToken)));
            } catch (Throwable $exception) {
                log_message('error', 'Password reset email failed: {type}', ['type' => $exception::class]);
            }
        }

        return redirect()->route('forgot.password')->with('success', 'If an Epic customer account exists for that address, we sent a password reset link. Check your inbox, spam, and quarantine folders.');
    }

    public function resetPassword(): string
    {
        $rawToken = strtolower(trim((string) $this->request->getGet('token')));
        $tokenValid = false;
        if (preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            $token = (new PasswordResetTokenModel())->where('token_hash', hash('sha256', $rawToken))->where('used_at', null)->first();
            $tokenValid = $token !== null && strtotime((string) $token['expires_at']) >= time();
        }

        return view('auth/reset_password', [
            'title' => 'Reset Password | Epic Storage Solutions', 'description' => 'Choose a new Epic Storage Solutions password.',
            'activePage' => 'login', 'canonicalUrl' => current_url(), 'socialImage' => null, 'robots' => 'noindex,nofollow',
            'token' => $rawToken, 'tokenValid' => $tokenValid,
        ]);
    }

    public function updatePassword(): RedirectResponse
    {
        $rawToken = strtolower(trim((string) $this->request->getPost('token')));
        $password = (string) $this->request->getPost('password');

        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return redirect()->route('forgot.password')->with('error', 'This reset link is not valid. Request a new one below.');
        }
        if (! $this->validateData(['password' => $password, 'password_confirm' => (string) $this->request->getPost('password_confirm')], [
            'password'         => 'required|min_length[12]|max_length[72]',
            'password_confirm' => 'required|matches[password]',
        ])) {
            return redirect()->to(site_url('reset-password?token=' . rawurlencode($rawToken)))->with('errors', $this->validator->getErrors());
        }

        $tokens = new PasswordResetTokenModel();
        $token = $tokens->where('token_hash', hash('sha256', $rawToken))->where('used_at', null)->first();
        if ($token === null || strtotime((string) $token['expires_at']) < time()) {
            return redirect()->route('forgot.password')->with('error', 'This reset link is missing, expired, or has already been used. Request a new link below.');
        }

        $users = new UserModel();
        $user = $users->find((int) $token['user_id']);
        if ($user === null || $user['role'] !== 'customer') {
            return redirect()->route('forgot.password')->with('error', 'This reset link is no longer associated with an active customer account.');
        }

        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transBegin();
        $claimed = $tokens->builder()->where('id', (int) $token['id'])->where('used_at', null)->update(['used_at' => $now]);
        if (! $claimed || $db->affectedRows() !== 1) {
            $db->transRollback();

            return redirect()->route('forgot.password')->with('error', 'This reset link is missing, expired, or has already been used. Request a new link below.');
        }

        $updated = $users->update((int) $user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        if (! $updated) {
            $db->transRollback();

            return redirect()->route('forgot.password')->with('error', 'Your password could not be updated. Please request a new link.');
        }
        $tokens->invalidateForUser((int) $user['id'], (int) $token['id']);

        if (! $db->transStatus()) {
            $db->transRollback();

            return redirect()->route('forgot.password')->with('error', 'Your password could not be updated. Please request a new link.');
        }
        $db->transCommit();

        $user = $users->find((int) $user['id']);
        if ($user === null) {
            return redirect()->route('login')->with('success', 'Your password has been reset. Please log in.');
        }
        (new AuthService())->startSession($user);

        return redirect()->to(site_url('account'))->with('success', 'Your password has been reset. Welcome back, ' . $user['name'] . '.');
    }

    public function magicLogin(): RedirectResponse
    {
        $rawToken = strtolower(trim((string) $this->request->getGet('token')));
        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return redirect()->route('login')->with('error', 'This login link is not valid.');
        }

        $tokens = new MagicLoginTokenModel();
        $token = $tokens->where('token_hash', hash('sha256', $rawToken))->where('used_at', null)->first();
        if ($token === null || strtotime((string) $token['expires_at']) < time()) {
            return redirect()->route('login')->with('error', 'This login link is missing, expired, or has already been used. Please log in with your password.');
        }

        $users = new UserModel();
        $user = $users->find((int) $token['user_id']);
        if ($user === null || $user['role'] !== 'customer' || $user['status'] !== 'active') {
            return redirect()->route('login')->with('error', 'This login link is no longer associated with an active customer account.');
        }

        $now = date('Y-m-d H:i:s');
        $db = db_connect();
        $db->transBegin();
        $claimed = $tokens->builder()->where('id', (int) $token['id'])->where('used_at', null)->update(['used_at' => $now]);
        if (! $claimed || $db->affectedRows() !== 1 || ! $db->transStatus()) {
            $db->transRollback();

            return redirect()->route('login')->with('error', 'This login link has already been used. Please log in with your password.');
        }
        $db->transCommit();

        (new AuthService())->startSession($user);
        $redirectPath = $this->safeRedirectPath((string) ($token['redirect_path'] ?? '')) ?? '/account';

        return redirect()->to(site_url(ltrim($redirectPath, '/')))->with('success', 'Welcome back, ' . $user['name'] . '.');
    }

    private function authView(string $view, string $title, string $description): string
    {
        return view($view, [
            'title'        => $title,
            'description'  => $description,
            'activePage'   => 'login',
            'canonicalUrl' => current_url(),
            'socialImage'  => null,
            'robots'       => 'noindex,nofollow',
        ]);
    }

    private function verificationNotice(): string
    {
        return view('auth/verify_email', [
            'title' => 'Confirm Your Email | Epic Storage Solutions',
            'description' => 'Confirm your Epic Storage Solutions customer account.',
            'activePage' => 'login', 'canonicalUrl' => current_url(), 'socialImage' => null, 'robots' => 'noindex,nofollow',
            'email' => session()->getFlashdata('verification_email'),
            'heading' => session()->getFlashdata('verification_heading') ?? 'Check your inbox',
            'message' => session()->getFlashdata('verification_message'),
            'error' => session()->getFlashdata('verification_error'),
            'showResend' => session()->getFlashdata('verification_resend') ?? true,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function verificationRedirect(array $data): RedirectResponse
    {
        $response = redirect()->route('verify.email');
        foreach ($data as $key => $value) {
            $response = $response->with($key, $value);
        }

        return $response;
    }

    private function verificationResult(string $heading, string $message, bool $showResend): string
    {
        return view('auth/verify_email', [
            'title' => $heading . ' | Epic Storage Solutions', 'description' => $message,
            'activePage' => 'login', 'canonicalUrl' => current_url(), 'socialImage' => null, 'robots' => 'noindex,nofollow',
            'heading' => $heading, 'message' => $message, 'showResend' => $showResend,
        ]);
    }

    private function safeRedirectPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path;
    }

    private function isCustomerPath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '//')) {
            return false;
        }

        $routePath = (string) parse_url($path, PHP_URL_PATH);

        return $routePath === '/account' || str_starts_with($routePath, '/account/');
    }

    private function serviceLabel(string $redirectPath): string
    {
        parse_str((string) parse_url($redirectPath, PHP_URL_QUERY), $query);

        return ($query['booking_type'] ?? '') === 'summer_advantage' ? 'Summer Advantage Program' : 'Summer Storage';
    }
}
