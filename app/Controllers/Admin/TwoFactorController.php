<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ActivityLog;
use App\Libraries\Totp;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

/** Optional authenticator-app (TOTP) second factor for the signed-in administrator. */
final class TwoFactorController extends BaseController
{
    protected $helpers = ['form', 'url'];
    private const REAUTH_TTL = 300;

    public function index(): string|RedirectResponse
    {
        if (! $this->isReauthenticated()) {
            return redirect()->route('admin.account.verify')->with('error', 'Verify your current password before changing two-factor settings.');
        }

        $user    = $this->currentUser();
        $enabled = (int) $user['totp_enabled'] === 1;
        $pending = null;
        if (! $enabled) {
            $totp = new Totp();
            if (! session()->get('admin_totp_pending')) {
                session()->set('admin_totp_pending', base64_encode(service('encrypter')->encrypt($totp->generateSecret())));
            }
            $secret  = service('encrypter')->decrypt(base64_decode((string) session()->get('admin_totp_pending'), true) ?: '');
            $pending = ['secret' => $secret, 'uri' => $totp->provisioningUri($secret, (string) $user['email'])];
        }

        return view('admin/account/two_factor', [
            'title' => 'Two-factor login', 'activeAdminPage' => 'account', 'enabled' => $enabled, 'pending' => $pending,
        ]);
    }

    public function enable(): RedirectResponse
    {
        if (! $this->isReauthenticated()) {
            return redirect()->route('admin.account.verify');
        }
        $stored = (string) session()->get('admin_totp_pending');
        if ($stored === '') {
            return redirect()->route('admin.account.two-factor');
        }
        $secret = service('encrypter')->decrypt(base64_decode($stored, true) ?: '');
        if (! (new Totp())->verify($secret, (string) $this->request->getPost('code'))) {
            return redirect()->back()->with('error', 'That code was not correct. Check your authenticator app and try again.');
        }

        (new UserModel())->update((int) session()->get('admin_user_id'), ['totp_secret' => $stored, 'totp_enabled' => 1]);
        session()->remove('admin_totp_pending');
        ActivityLog::record('two_factor.enabled');

        return redirect()->route('admin.account.two-factor')->with('success', 'Two-factor login is now on. You will be asked for a code each time you sign in.');
    }

    public function disable(): RedirectResponse
    {
        if (! $this->isReauthenticated()) {
            return redirect()->route('admin.account.verify');
        }
        $user = $this->currentUser();
        $code = (string) $this->request->getPost('code');
        if ((int) $user['totp_enabled'] !== 1 || ! (new Totp())->verify(Totp::decryptSecret((string) $user['totp_secret']), $code)) {
            return redirect()->back()->with('error', 'Enter a current code from your authenticator app to turn two-factor login off.');
        }

        (new UserModel())->update((int) $user['id'], ['totp_secret' => null, 'totp_enabled' => 0]);
        ActivityLog::record('two_factor.disabled');

        return redirect()->route('admin.account.two-factor')->with('success', 'Two-factor login is now off.');
    }

    /** @return array<string, mixed> */
    private function currentUser(): array
    {
        return (new UserModel())->find((int) session()->get('admin_user_id')) ?? [];
    }

    private function isReauthenticated(): bool
    {
        return (int) session()->get('admin_reauth_user_id') === (int) session()->get('admin_user_id')
            && (int) session()->get('admin_reauth_at') >= time() - self::REAUTH_TTL;
    }
}
