<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\MagicLoginTokenModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class AuthService
{
    public function __construct(
        private readonly ?UserModel $users = null,
        private readonly ?Session $session = null,
    ) {
    }

    public const MAX_FAILED_LOGINS = 5;
    public const LOCKOUT_MINUTES   = 15;

    /**
     * Verifies credentials. Repeated failures lock the account for a while (the lock is
     * silent: the caller just gets null, same as for a wrong password).
     *
     * @return array<string, mixed>|null
     */
    public function attempt(string $email, string $password, ?string $expectedRole = null, bool $startSession = true): ?array
    {
        $users = $this->userModel();
        $users->where('email', mb_strtolower(trim($email)));

        if ($expectedRole !== null) {
            $users->where('role', $expectedRole);
        }

        $user = $users->first();

        if ($user === null) {
            return null;
        }

        if (! empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            return null;
        }

        if ($user['status'] !== 'active' || ! password_verify($password, $user['password_hash'])) {
            $this->recordFailure($user);

            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->userModel()->update($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        if ($startSession) {
            $this->completeLogin($user);
        }

        return $user;
    }

    /** Finishes a login once every required factor has been verified. @param array<string, mixed> $user */
    public function completeLogin(array $user): void
    {
        $this->userModel()->update($user['id'], [
            'last_login_at'      => Time::now()->toDateTimeString(),
            'failed_login_count' => 0,
            'locked_until'       => null,
        ]);
        $this->startSession($user);
    }

    /** @param array<string, mixed> $user */
    private function recordFailure(array $user): void
    {
        $count  = (int) ($user['failed_login_count'] ?? 0) + 1;
        $update = ['failed_login_count' => $count];
        if ($count >= self::MAX_FAILED_LOGINS) {
            $update = ['failed_login_count' => 0, 'locked_until' => date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60)];
        }
        $this->userModel()->update($user['id'], $update);
    }

    /** @param array<string, mixed> $user */
    public function startSession(array $user): void
    {
        $role = (string) ($user['role'] ?? '');
        if (! in_array($role, ['customer', 'admin'], true)) {
            throw new InvalidArgumentException('Unsupported authentication role.');
        }

        $session = $this->session();
        $session->regenerate(true);
        $session->remove(['user_id', 'user_name', 'user_email', 'user_role', 'is_logged_in']);
        $session->set([
            $role . '_user_id'      => (int) $user['id'],
            $role . '_user_name'    => (string) $user['name'],
            $role . '_user_email'   => (string) $user['email'],
            $role . '_is_logged_in' => true,
        ]);
    }

    /**
     * Creates a one-time login link for a customer that also carries them to
     * $redirectPath after sign-in, so a notification email's call-to-action
     * (pay balance, schedule pickup) can skip the manual login step.
     */
    public function createMagicLoginUrl(int $userId, string $redirectPath): ?string
    {
        $rawToken = bin2hex(random_bytes(32));

        try {
            $tokens = new MagicLoginTokenModel();
            $tokens->insert([
                'user_id'       => $userId,
                'token_hash'    => hash('sha256', $rawToken),
                'redirect_path' => $redirectPath,
                'expires_at'    => (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s'),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            return null;
        }

        return site_url('magic-login?token=' . rawurlencode($rawToken));
    }

    public function logout(string $role): void
    {
        if (! in_array($role, ['customer', 'admin'], true)) {
            throw new InvalidArgumentException('Unsupported authentication role.');
        }

        $session = $this->session();
        $session->remove([
            $role . '_user_id',
            $role . '_user_name',
            $role . '_user_email',
            $role . '_is_logged_in',
            $role . '_intended_url',
        ]);

        if ($role === 'admin') {
            $session->remove(['admin_reauth_user_id', 'admin_reauth_at']);
        }

        $session->regenerate(true);
    }

    private function userModel(): UserModel
    {
        return $this->users ?? new UserModel();
    }

    private function session(): Session
    {
        return $this->session ?? service('session');
    }
}
