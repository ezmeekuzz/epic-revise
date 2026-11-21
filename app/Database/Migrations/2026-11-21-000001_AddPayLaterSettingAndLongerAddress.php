<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Client edits of November 21:
 * - an admin switch that hides the "Pay later" option at checkout (on by default);
 * - room/address fields hold a dorm room, suite or street address, so they grow from 30 to 120 characters.
 */
final class AddPayLaterSettingAndLongerAddress extends Migration
{
    public function up(): void
    {
        if ($this->db->table('app_settings')->where('setting_key', 'pay_later_enabled')->countAllResults() === 0) {
            $this->db->table('app_settings')->insert([
                'setting_key' => 'pay_later_enabled', 'setting_value' => '1', 'group_name' => 'payment',
                'is_encrypted' => 0, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->forge->modifyColumn('bookings', ['room_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true]]);
        $this->forge->modifyColumn('users', ['dorm_room_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true]]);
    }

    public function down(): void
    {
        $this->db->table('app_settings')->where('setting_key', 'pay_later_enabled')->delete();
        $this->forge->modifyColumn('bookings', ['room_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true]]);
        $this->forge->modifyColumn('users', ['dorm_room_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true]]);
    }
}
