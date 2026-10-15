<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddSquarePaymentSettings extends Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $settings = [
            ['setting_key' => 'square_application_id', 'setting_value' => '', 'group_name' => 'payment', 'is_encrypted' => 0, 'updated_at' => $now],
            ['setting_key' => 'square_access_token', 'setting_value' => '', 'group_name' => 'payment', 'is_encrypted' => 1, 'updated_at' => $now],
            ['setting_key' => 'square_location_id', 'setting_value' => '', 'group_name' => 'payment', 'is_encrypted' => 0, 'updated_at' => $now],
            ['setting_key' => 'square_webhook_signature_key', 'setting_value' => '', 'group_name' => 'payment', 'is_encrypted' => 1, 'updated_at' => $now],
        ];

        foreach ($settings as $setting) {
            if ($this->db->table('app_settings')->where('setting_key', $setting['setting_key'])->countAllResults() === 0) {
                $this->db->table('app_settings')->insert($setting);
            }
        }

        $this->db->table('app_settings')
            ->where('setting_key', 'payment_provider')
            ->where('setting_value', 'manual')
            ->update(['setting_value' => 'square', 'updated_at' => $now]);
    }

    public function down(): void
    {
        $this->db->table('app_settings')->whereIn('setting_key', [
            'square_application_id',
            'square_access_token',
            'square_location_id',
            'square_webhook_signature_key',
        ])->delete();
    }
}
