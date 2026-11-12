<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds an admin-controlled credit card service fee (a percentage added to each order total).
 * Each booking snapshots the percentage and amount so historical orders never change.
 */
final class AddServiceFee extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'service_fee_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => '0.00', 'after' => 'discount_amount'],
            'service_fee_amount'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00', 'after' => 'service_fee_percent'],
        ]);

        if ($this->db->table('app_settings')->where('setting_key', 'service_fee_percent')->countAllResults() === 0) {
            $this->db->table('app_settings')->insert([
                'setting_key' => 'service_fee_percent', 'setting_value' => '0', 'group_name' => 'pricing',
                'is_encrypted' => 0, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('bookings', ['service_fee_percent', 'service_fee_amount']);
        $this->db->table('app_settings')->where('setting_key', 'service_fee_percent')->delete();
    }
}
