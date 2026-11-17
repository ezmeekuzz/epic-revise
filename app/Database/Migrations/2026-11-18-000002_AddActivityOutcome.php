<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Lets the activity log tell a saved change from a rejected attempt. */
final class AddActivityOutcome extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('admin_activity_log', [
            'outcome' => ['type' => 'VARCHAR', 'constraint' => 16, 'default' => 'ok', 'after' => 'action'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('admin_activity_log', 'outcome');
    }
}
