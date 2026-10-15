<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Removes columns added by AddReferenceBookingAttributes that turned out to be
 * exact duplicates of existing fields (always written with the same value, never
 * read back) or dead weight (never written or read at all).
 */
final class DropRedundantBookingAttributes extends Migration
{
    public function up(): void
    {
        $prefix = $this->db->getPrefix();

        $this->db->query("ALTER TABLE `{$prefix}booking_items` DROP FOREIGN KEY `{$prefix}booking_items_reference_size_fk`");
        $this->forge->dropColumn('booking_items', ['size_id', 'price', 'totalamount', 'is_original_order', 'is_balanced', 'order_date']);
        $this->forge->dropColumn('bookings', ['picking_date', 'picking_time', 'is_checked', 'amount_received', 'manual_payment', 'manual_payment_note']);
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();

        $this->forge->addColumn('bookings', [
            'picking_date' => ['type' => 'DATE', 'null' => true],
            'picking_time' => ['type' => 'TIME', 'null' => true],
            'is_checked' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'amount_received' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'manual_payment' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'manual_payment_note' => ['type' => 'TEXT', 'null' => true],
        ]);

        $this->forge->addColumn('booking_items', [
            'size_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'totalamount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'is_original_order' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_balanced' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'order_date' => ['type' => 'DATE', 'null' => true],
        ]);

        $this->db->query("UPDATE `{$prefix}bookings` SET `picking_date` = `pickup_date`, `picking_time` = `pickup_time`");
        $this->db->query("UPDATE `{$prefix}booking_items` SET `size_id` = `item_size_id`, `price` = `unit_price`, `totalamount` = `line_total`, `is_balanced` = 'No', `order_date` = DATE(`created_at`)");

        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD INDEX `{$prefix}booking_items_reference_size_idx` (`size_id`)");
        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD CONSTRAINT `{$prefix}booking_items_reference_size_fk` FOREIGN KEY (`size_id`) REFERENCES `{$prefix}item_sizes` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
    }
}
