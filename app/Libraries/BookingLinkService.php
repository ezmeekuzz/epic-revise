<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\BookingAccessTokenModel;
use App\Models\BookingModel;
use App\Models\UserModel;
use DateTimeImmutable;
use Throwable;

/**
 * Connects bookings made without an account (walk-in or online) to customer accounts.
 *
 * The one rule: a booking joins an account only after ownership of the email is proven,
 * by staff in person, by logging in, by email confirmation, or by a click from the inbox.
 * Typing an email into a form never links a booking or logs anyone in.
 */
final class BookingLinkService
{
    public const PURPOSE_PAY   = 'pay';
    public const PURPOSE_CLAIM = 'claim';

    private const PAY_DAYS          = 14;
    private const CLAIM_DAYS        = 7;
    private const SESSION_KEY       = 'guest_booking_access';
    private const SESSION_MAX_ITEMS = 10;

    /**
     * Gives every unowned booking with this account's email to the account, plus its payments.
     * Call only once the email is proven: after confirmation, login, a claim link, or by staff.
     *
     * @param array<string, mixed> $user
     */
    public function linkUnclaimed(array $user): int
    {
        if (! $this->isLinkableCustomer($user)) {
            return 0;
        }

        $email = mb_strtolower(trim((string) $user['email']));
        $userId = (int) $user['id'];
        $bookingIds = array_map('intval', array_column(
            (new BookingModel())->select('id')->where('customer_email', $email)->where('user_id', null)->findAll(),
            'id',
        ));
        if ($bookingIds === []) {
            return 0;
        }

        $db = db_connect();
        $db->transBegin();
        try {
            // user_id IS NULL is repeated so a concurrent link can never move an owned booking.
            $db->table('bookings')->whereIn('id', $bookingIds)->where('user_id', null)->update(['user_id' => $userId]);
            $linked = $db->affectedRows();
            $db->table('payments')->whereIn('booking_id', $bookingIds)->where('user_id', null)->update(['user_id' => $userId]);
            if (! $db->transStatus()) {
                throw new \RuntimeException('Booking link transaction failed.');
            }
            $db->transCommit();

            return $linked;
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Linking bookings to customer {user} failed: {type}', ['user' => $userId, 'type' => $exception::class]);

            return 0;
        }
    }

    /** @return array<string, mixed>|null An active customer account that owns this email. */
    public function activeCustomerByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return (new UserModel())->where('email', $email)->where('role', 'customer')->where('status', 'active')->first();
    }

    /** Issues a link token and returns the raw value. Only its SHA-256 hash is stored. */
    public function issueToken(int $bookingId, string $purpose): ?string
    {
        if (! in_array($purpose, [self::PURPOSE_PAY, self::PURPOSE_CLAIM], true)) {
            return null;
        }

        $rawToken = bin2hex(random_bytes(32));
        $days = $purpose === self::PURPOSE_PAY ? self::PAY_DAYS : self::CLAIM_DAYS;
        try {
            (new BookingAccessTokenModel())->insert([
                'booking_id' => $bookingId,
                'purpose' => $purpose,
                'token_hash' => hash('sha256', $rawToken),
                'expires_at' => (new DateTimeImmutable('+' . $days . ' days'))->format('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Booking {purpose} token could not be issued: {type}', ['purpose' => $purpose, 'type' => $exception::class]);

            return null;
        }

        return $rawToken;
    }

    /** @return array<string, mixed>|null A token row that is well-formed, unexpired and (for claims) unused. */
    public function findValidToken(string $rawToken, string $purpose): ?array
    {
        $rawToken = strtolower(trim($rawToken));
        if (! preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }

        $token = (new BookingAccessTokenModel())
            ->where('token_hash', hash('sha256', $rawToken))
            ->where('purpose', $purpose)
            ->first();
        if ($token === null || strtotime((string) $token['expires_at']) < time()) {
            return null;
        }
        if ($purpose === self::PURPOSE_CLAIM && $token['used_at'] !== null) {
            return null;
        }

        return $token;
    }

    /** Atomically marks a token used. Returns false when another request used it first. */
    public function consumeToken(int $tokenId): bool
    {
        $db = db_connect();
        $db->table('booking_access_tokens')->where('id', $tokenId)->where('used_at', null)->update(['used_at' => date('Y-m-d H:i:s')]);

        return $db->affectedRows() === 1;
    }

    /** Lets this browser session view and pay one booking (right after creating it, or via a pay link). */
    public function grantGuestAccess(int $bookingId): void
    {
        $session = service('session');
        $ids = array_values(array_filter((array) $session->get(self::SESSION_KEY), 'is_int'));
        $ids = array_values(array_unique([...$ids, $bookingId]));
        $session->set(self::SESSION_KEY, array_slice($ids, -self::SESSION_MAX_ITEMS));
    }

    public function hasGuestAccess(int $bookingId): bool
    {
        return in_array($bookingId, (array) service('session')->get(self::SESSION_KEY), true);
    }

    /** Customer-facing payment link for a booking email. */
    public function paymentUrl(array $booking): string
    {
        $bookingId = (int) $booking['id'];
        if (! empty($booking['user_id'])) {
            return (new AuthService())->createMagicLoginUrl((int) $booking['user_id'], '/account/bookings/' . $bookingId . '/payment') ?? site_url('login');
        }

        $token = $this->issueToken($bookingId, self::PURPOSE_PAY);

        return $token !== null ? site_url('book/pay/' . $token) : site_url('contact');
    }

    /**
     * Customer-facing link to manage a booking from an account.
     * Linked booking: one-click login. Email has an account: "add to my account" claim link.
     * No account: registration with the email pre-filled.
     *
     * @return array{label: string, url: string, hasAccount: bool}
     */
    public function accountLink(array $booking, string $accountPath): array
    {
        if (! empty($booking['user_id'])) {
            return [
                'label' => 'Open my booking',
                'url' => (new AuthService())->createMagicLoginUrl((int) $booking['user_id'], $accountPath) ?? site_url('login'),
                'hasAccount' => true,
            ];
        }

        if ($this->activeCustomerByEmail((string) $booking['customer_email']) !== null) {
            $token = $this->issueToken((int) $booking['id'], self::PURPOSE_CLAIM);

            return [
                'label' => 'Add this booking to my account',
                'url' => $token !== null ? site_url('book/claim/' . $token) : site_url('login'),
                'hasAccount' => true,
            ];
        }

        return [
            'label' => 'Set up my account',
            'url' => site_url('register?email=' . rawurlencode((string) $booking['customer_email'])),
            'hasAccount' => false,
        ];
    }

    /** j***@gmail.com style masking for pages anyone can see. */
    public static function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return '***';
        }

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }

    /** @param array<string, mixed> $user */
    private function isLinkableCustomer(array $user): bool
    {
        return (int) ($user['id'] ?? 0) > 0
            && ($user['role'] ?? '') === 'customer'
            && ($user['status'] ?? '') === 'active'
            && trim((string) ($user['email'] ?? '')) !== '';
    }
}
