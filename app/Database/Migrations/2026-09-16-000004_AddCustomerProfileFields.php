<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class AddCustomerProfileFields extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'student_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'email'],
            'phone_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'student_id'],
            'street_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'phone_number'],
            'street_name' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'street_number'],
            'dorm_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'street_name'],
            'dorm_room_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'dorm_id'],
            'parent_phone_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'dorm_room_number'],
            'parent_email_address' => ['type' => 'VARCHAR', 'constraint' => 254, 'null' => true, 'after' => 'parent_phone_number'],
        ]);

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}users` ADD CONSTRAINT `{$prefix}users_dorm_id_foreign` FOREIGN KEY (`dorm_id`) REFERENCES `{$prefix}dorms` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('users', $this->db->getPrefix() . 'users_dorm_id_foreign');
        $this->forge->dropColumn('users', [
            'student_id',
            'phone_number',
            'street_number',
            'street_name',
            'dorm_id',
            'dorm_room_number',
            'parent_phone_number',
            'parent_email_address',
        ]);
    }
}
