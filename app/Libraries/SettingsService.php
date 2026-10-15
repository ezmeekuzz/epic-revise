<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AppSettingModel;
use RuntimeException;
use Throwable;

final class SettingsService
{
    /** @var array<string, list<string>> */
    private const GROUPS = [
        'general' => ['homepage_message_title', 'homepage_message_body', 'current_terms_text', 'business_name', 'support_email', 'business_phone', 'business_timezone'],
        'booking' => ['bookings_enabled', 'booking_unavailable_message', 'daily_booking_capacity', 'minimum_advance_hours', 'pickup_start_time', 'pickup_end_time', 'slot_duration_minutes', 'blackout_dates'],
        'pricing' => ['summer_storage_base_price', 'summer_storage_additional_box_price', 'summer_advantage_box_price', 'summer_school_fee'],
        'email' => ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_crypto', 'email_from_address', 'email_from_name'],
        'payment' => [
            'payments_enabled', 'payment_provider', 'payment_mode', 'payment_currency',
            'payment_public_key', 'payment_secret_key', 'payment_webhook_secret',
            'square_application_id', 'square_access_token', 'square_location_id', 'square_webhook_signature_key',
        ],
    ];

    /** @var list<string> */
    private const SECRET_KEYS = [
        'smtp_password', 'payment_secret_key', 'payment_webhook_secret',
        'square_access_token', 'square_webhook_signature_key',
    ];

    /** @return array<string, string|bool> */
    public function displayGroup(string $group): array
    {
        $values = [];

        foreach ($this->rows($group) as $row) {
            $key = (string) $row['setting_key'];
            if (in_array($key, self::SECRET_KEYS, true)) {
                $values[$key]                = '';
                $values[$key . '_configured'] = (string) $row['setting_value'] !== '';
            } else {
                $values[$key] = (string) ($row['setting_value'] ?? '');
            }
        }

        return $values;
    }

    public function get(string $key, string $default = ''): string
    {
        $row = (new AppSettingModel())->find($key);
        if ($row === null || (string) $row['setting_value'] === '') {
            return $default;
        }

        if ((int) $row['is_encrypted'] !== 1) {
            return (string) $row['setting_value'];
        }

        try {
            $decoded = base64_decode((string) $row['setting_value'], true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid encrypted setting encoding.');
            }

            return service('encrypter')->decrypt($decoded);
        } catch (Throwable $exception) {
            log_message('error', 'Unable to decrypt application setting {key}: {type}', ['key' => $key, 'type' => $exception::class]);

            return $default;
        }
    }

    /** @param array<string, string> $values @param list<string> $clearSecrets */
    public function saveGroup(string $group, array $values, array $clearSecrets, int $actorId): void
    {
        $allowed = self::GROUPS[$group] ?? null;
        if ($allowed === null) {
            throw new RuntimeException('Unknown settings group.');
        }

        $model = new AppSettingModel();
        $db    = db_connect();
        $db->transStart();

        foreach ($allowed as $key) {
            if (in_array($key, self::SECRET_KEYS, true)) {
                if (in_array($key, $clearSecrets, true)) {
                    $stored = '';
                } elseif (($values[$key] ?? '') !== '') {
                    $stored = base64_encode(service('encrypter')->encrypt((string) $values[$key]));
                } else {
                    continue;
                }
            } else {
                $stored = (string) ($values[$key] ?? '');
            }

            $model->update($key, [
                'setting_value' => $stored,
                'updated_by'    => $actorId,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new RuntimeException('The settings could not be saved.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $group): array
    {
        if (! isset(self::GROUPS[$group])) {
            return [];
        }

        return (new AppSettingModel())->where('group_name', $group)->findAll();
    }
}
