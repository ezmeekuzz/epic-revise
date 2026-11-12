<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Account lockout, optional two-factor login (TOTP) and an admin activity log. */
final class AddAdminSecurity extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'failed_login_count' => ['type' => 'INT', 'constraint' => 5, 'unsigned' => true, 'default' => 0],
            'locked_until'       => ['type' => 'DATETIME', 'null' => true],
            'totp_secret'        => ['type' => 'TEXT', 'null' => true],
            'totp_enabled'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'admin_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'admin_name'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'action'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'target'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'detail'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->addKey('admin_user_id');
        $this->forge->createTable('admin_activity_log', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('admin_activity_log', true);
        $this->forge->dropColumn('users', ['failed_login_count', 'locked_until', 'totp_secret', 'totp_enabled']);
    }
}
