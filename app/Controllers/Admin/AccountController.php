<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AuthService;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\RedirectResponse;

final class AccountController extends BaseController
{
    protected $helpers = ['form', 'url'];
    private const REAUTH_TTL = 300;

    public function edit(): string|RedirectResponse
    {
        if (! $this->isReauthenticated()) {
            return redirect()->route('admin.account.verify');
        }

        return view('admin/account/edit', [
            'title'           => 'Admin Account',
            'activeAdminPage' => 'account',
            'user'            => $this->currentUser(),
        ]);
    }

    public function verify(): string
    {
        return view('admin/account/verify', [
            'title' => 'Verify Admin Account',
            'activeAdminPage' => 'account',
        ]);
    }

    public function verifyPassword(): RedirectResponse
    {
        $password = (string) $this->request->getPost('password');
        if (! $this->validateData(['password' => $password], ['password' => 'required|max_length[72]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $key = 'admin_reauth_' . hash('sha256', (string) session()->get('admin_user_id') . '|' . $this->request->getIPAddress());
        if (! service('throttler')->check($key, 5, MINUTE * 5)) {
            return redirect()->back()->with('error', 'Too many verification attempts. Please wait five minutes and try again.');
        }

        $user = $this->currentUser();
        if (! password_verify($password, (string) $user['password_hash'])) {
            return redirect()->back()->with('error', 'That password was not accepted.');
        }

        session()->set([
            'admin_reauth_user_id' => (int) $user['id'],
            'admin_reauth_at' => time(),
        ]);

        return redirect()->route('admin.account')->with('success', 'Identity verified. You can update administrator credentials for five minutes.');
    }

    public function updateProfile(): RedirectResponse
    {
        if ($redirect = $this->requireReauthentication()) {
            return $redirect;
        }

        $id   = (int) session()->get('admin_user_id');
        $data = [
            'name'  => trim((string) $this->request->getPost('name')),
            'email' => mb_strtolower(trim((string) $this->request->getPost('email'))),
        ];

        if (! $this->validateData($data, [
            'name'  => 'required|min_length[2]|max_length[100]',
            'email' => 'required|valid_email|max_length[254]|is_unique[users.email,id,' . $id . ']',
        ])) {
            return redirect()->back()->withInput()->with('profileErrors', $this->validator->getErrors());
        }

        $users = new UserModel();
        try {
            $users->update($id, $data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'Your profile could not be updated. The email may already be in use.');
        }

        $user = $users->find($id);
        if ($user !== null) {
            (new AuthService())->startSession($user);
        }

        $this->clearReauthentication();

        return redirect()->route('admin.account')->with('success', 'Admin profile updated.');
    }

    public function updatePassword(): RedirectResponse
    {
        if ($redirect = $this->requireReauthentication()) {
            // Preserve a safe direct-submit path for API clients: a valid current
            // password in the same request establishes the short re-auth window.
            $current = (string) $this->request->getPost('current_password');
            $user = $this->currentUser();
            if ($current === '' || ! password_verify($current, (string) $user['password_hash'])) {
                return $redirect;
            }
            session()->set(['admin_reauth_user_id' => (int) $user['id'], 'admin_reauth_at' => time()]);
        }

        $data = [
            'current_password' => (string) $this->request->getPost('current_password'),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
        ];

        if (! $this->validateData($data, [
            'current_password' => 'required|max_length[72]',
            'password'         => 'required|min_length[12]|max_length[72]',
            'password_confirm' => 'required|matches[password]',
        ])) {
            return redirect()->back()->with('passwordErrors', $this->validator->getErrors());
        }

        $user = $this->currentUser();
        if (! password_verify($data['current_password'], $user['password_hash'])) {
            return redirect()->back()->with('passwordErrors', ['current_password' => 'The current password is incorrect.']);
        }

        (new UserModel())->update($user['id'], ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)]);
        $this->clearReauthentication();
        session()->regenerate(true);

        return redirect()->route('admin.account')->with('success', 'Password changed successfully.');
    }

    /** @return array<string, mixed> */
    private function currentUser(): array
    {
        $user = (new UserModel())->find((int) session()->get('admin_user_id'));

        if ($user === null) {
            throw new DatabaseException('The signed-in user could not be found.');
        }

        return $user;
    }

    private function isReauthenticated(): bool
    {
        return (int) session()->get('admin_reauth_user_id') === (int) session()->get('admin_user_id')
            && (int) session()->get('admin_reauth_at') >= time() - self::REAUTH_TTL;
    }

    private function requireReauthentication(): ?RedirectResponse
    {
        if ($this->isReauthenticated()) {
            return null;
        }

        return redirect()->route('admin.account.verify')->with('error', 'Verify your current password before changing administrator credentials.');
    }

    private function clearReauthentication(): void
    {
        session()->remove(['admin_reauth_user_id', 'admin_reauth_at']);
    }
}
