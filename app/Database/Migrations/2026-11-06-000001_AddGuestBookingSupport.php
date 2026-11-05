<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Supports bookings made without an account:
 * - parent contact details are snapshotted on the booking, so staff still see them when no account is linked;
 * - booking_access_tokens back the emailed "pay this booking" and "add this booking to my account" links.
 */
final class AddGuestBookingSupport extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'parent_phone_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'student_id'],
            'parent_email_address' => ['type' => 'VARCHAR', 'constraint' => 254, 'null' => true, 'after' => 'parent_phone_number'],
        ]);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            // pay   = view and pay this one booking, reusable until it expires (a card can be declined and retried)
            // claim = attach this booking to the account that owns its email, single use
            'purpose' => ['type' => 'VARCHAR', 'constraint' => 20],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey(['booking_id', 'purpose']);
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_access_tokens');
    }

    public function down(): void
    {
        $this->forge->dropTable('booking_access_tokens', true);
        $this->forge->dropColumn('bookings', ['parent_phone_number', 'parent_email_address']);
    }
}
