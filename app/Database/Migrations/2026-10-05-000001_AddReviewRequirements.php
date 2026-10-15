<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddReviewRequirements extends Migration
{
    public function up(): void
    {
        $fields = [];
        foreach (['shipping_street' => 190, 'shipping_city' => 100, 'shipping_state' => 100, 'shipping_zip' => 20] as $name => $length) {
            $fields[$name] = ['type' => 'VARCHAR', 'constraint' => $length, 'null' => true];
        }
        // Existing customers retain their data and complete missing details on their next request.
        $this->forge->addColumn('users', $fields);
        $this->forge->addColumn('bookings', [
            'terms_snapshot' => ['type' => 'TEXT', 'null' => true],
            'terms_accepted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'request_token' => ['type' => 'VARCHAR', 'constraint' => 64],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('request_token');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_additions');
        foreach (['homepage_message_title' => 'Welcome back to HPU', 'homepage_message_body' => 'We start taking orders in September for the 2027 school year. Check back on our website in early September to secure your spot.', 'current_terms_text' => ''] as $key => $value) {
            $this->db->table('app_settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'group_name' => 'general', 'is_encrypted' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down(): void
    {
        $this->db->table('app_settings')->whereIn('setting_key', ['homepage_message_title', 'homepage_message_body', 'current_terms_text'])->delete();
        $this->forge->dropTable('booking_additions', true);
        $this->forge->dropColumn('bookings', ['terms_snapshot', 'terms_accepted_at']);
        $this->forge->dropColumn('users', ['shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip']);
    }
}
