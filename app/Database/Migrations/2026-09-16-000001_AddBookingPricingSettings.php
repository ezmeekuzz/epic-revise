<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddBookingPricingSettings extends Migration
{
    public function up(): void
    {
        $defaults = [
            'summer_storage_base_price' => '450.00',
            'summer_advantage_box_price' => '50.00',
        ];

        foreach ($defaults as $key => $value) {
            if ($this->db->table('app_settings')->where('setting_key', $key)->countAllResults() === 0) {
                $this->db->table('app_settings')->insert([
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'group_name' => 'pricing',
                    'is_encrypted' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->db->table('app_settings')->whereIn('setting_key', [
            'summer_storage_base_price',
            'summer_advantage_box_price',
        ])->delete();
    }
}
