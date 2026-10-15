<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;

/** Shared customer data contract for registration, self-service, and administration. */
final class CustomerDetails
{
    public const FIELDS = [
        'student_id' => ['Student ID', 'text', 50, 'off'],
        'phone_number' => ['Student phone number', 'tel', 30, 'tel'],
        'parent_phone_number' => ['Parent phone number', 'tel', 30, 'off'],
        'parent_email_address' => ['Parent email address', 'email', 254, 'off'],
        'shipping_street' => ['Street address (including apartment)', 'text', 190, 'shipping address-line1'],
        'shipping_city' => ['City', 'text', 100, 'shipping address-level2'],
        'shipping_state' => ['State / province', 'text', 100, 'shipping address-level1'],
        'shipping_zip' => ['ZIP / postal code', 'text', 20, 'shipping postal-code'],
    ];

    public static function input(IncomingRequest $request): array
    {
        $data = [];
        foreach (self::FIELDS as $field => $definition) {
            $posted = $request->getPost($field);
            $value = is_scalar($posted) ? trim((string) $posted) : '';
            $data[$field] = $field === 'parent_email_address' ? mb_strtolower($value) : $value;
        }
        return $data;
    }

    public static function rules(): array
    {
        $rules = [];
        foreach (self::FIELDS as $field => [$label, $type, $length]) {
            $rules[$field] = 'required|max_length[' . $length . ']';
            if ($type === 'email') {
                $rules[$field] .= '|valid_email';
            } elseif ($type === 'tel') {
                $rules[$field] .= '|min_length[7]|regex_match[/^[+0-9(). \-]+$/]';
            }
        }
        return $rules;
    }

    public static function complete(array $user): bool
    {
        foreach (array_keys(self::FIELDS) as $field) {
            if (trim((string) ($user[$field] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }
}
