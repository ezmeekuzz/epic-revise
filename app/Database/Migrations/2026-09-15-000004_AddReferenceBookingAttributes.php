<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Keeps the reference Epic booking fields available while retaining this
 * application's normalized customer, pickup, payment, and size relationships.
 */
final class AddReferenceBookingAttributes extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('bookings', [
            'ordernumber' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'card_holder_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'booking_date' => ['type' => 'DATE', 'null' => true],
            'base_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'additional_box_quantity' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
            'additional_box_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'addtl_box_total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'study_abroad_additional_storage_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'picking_date' => ['type' => 'DATE', 'null' => true],
            'picking_time' => ['type' => 'TIME', 'null' => true],
            'row_in_warehouse' => ['type' => 'INT', 'constraint' => 10, 'null' => true],
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

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD INDEX `{$prefix}booking_items_reference_size_idx` (`size_id`)");
        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD CONSTRAINT `{$prefix}booking_items_reference_size_fk` FOREIGN KEY (`size_id`) REFERENCES `{$prefix}item_sizes` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
        $this->db->query("UPDATE `{$prefix}bookings` SET `booking_date` = DATE(`created_at`) WHERE `booking_date` IS NULL");
        $this->db->query("UPDATE `{$prefix}bookings` SET `picking_date` = `pickup_date`, `picking_time` = `pickup_time` WHERE `picking_date` IS NULL");
        $this->db->query("UPDATE `{$prefix}booking_items` SET `size_id` = `item_size_id`, `price` = `unit_price`, `totalamount` = `line_total`, `is_balanced` = 'No', `order_date` = DATE(`created_at`) WHERE `size_id` IS NULL");
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}booking_items` DROP FOREIGN KEY `{$prefix}booking_items_reference_size_fk`");
        $this->forge->dropColumn('booking_items', ['size_id', 'price', 'totalamount', 'is_original_order', 'is_balanced', 'order_date']);
        $this->forge->dropColumn('bookings', ['ordernumber', 'card_holder_name', 'booking_date', 'base_price', 'additional_box_quantity', 'additional_box_amount', 'addtl_box_total_amount', 'study_abroad_additional_storage_price', 'notes', 'picking_date', 'picking_time', 'row_in_warehouse', 'is_checked', 'amount_received', 'manual_payment', 'manual_payment_note']);
    }
}
