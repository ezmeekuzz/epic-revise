<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Makes email unique per role, so one address can hold both an admin and a customer account. */
final class AllowSameEmailAcrossRoles extends Migration
{
    public function up(): void
    {
        $this->db->query('ALTER TABLE `users` DROP INDEX `email`, ADD UNIQUE KEY `users_email_role` (`email`, `role`)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE `users` DROP INDEX `users_email_role`, ADD UNIQUE KEY `email` (`email`)');
    }
}
