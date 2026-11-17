<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Remembers the last accepted authenticator time step so a code cannot be used twice. */
final class AddTotpLastStep extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'totp_last_step' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'totp_last_step');
    }
}
