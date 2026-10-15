<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class ExpandBookingOperations extends Migration
{
    public function up(): void
    {
        $this->createDorms();
        $this->createItemSizes();
        $this->expandBookings();
        $this->expandBookingItems();
        $this->migrateExistingData();
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();

        $this->db->query("ALTER TABLE `{$prefix}booking_items` DROP FOREIGN KEY `{$prefix}booking_items_size_fk`");
        $this->forge->dropColumn('booking_items', ['item_size_id', 'size_name']);
        $this->db->query("ALTER TABLE `{$prefix}bookings` DROP FOREIGN KEY `{$prefix}bookings_dorm_fk`");
        $this->forge->dropColumn('bookings', [
            'booking_type', 'dorm_id', 'dropoff_date', 'dropoff_time', 'dropoff_location',
            'dropoff_room_number', 'pickup_status', 'dropoff_status',
        ]);
        $this->forge->dropTable('item_sizes', true);
        $this->forge->dropTable('dorms', true);
    }

    private function createDorms(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'address' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pickup_notes' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('is_active');
        $this->forge->createTable('dorms');
    }

    private function createItemSizes(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'dimensions' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['item_id', 'name']);
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey(['item_id', 'is_active']);
        $this->forge->addForeignKey('item_id', 'items', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('item_sizes');
    }

    private function expandBookings(): void
    {
        $this->forge->addColumn('bookings', [
            'booking_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'summer_storage', 'after' => 'service_name'],
            'dorm_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'student_id'],
            'dropoff_date' => ['type' => 'DATE', 'null' => true, 'after' => 'room_number'],
            'dropoff_time' => ['type' => 'TIME', 'null' => true, 'after' => 'dropoff_date'],
            'dropoff_location' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true, 'after' => 'dropoff_time'],
            'dropoff_room_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'dropoff_location'],
            'pickup_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unscheduled', 'after' => 'dropoff_room_number'],
            'dropoff_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unscheduled', 'after' => 'pickup_status'],
        ]);

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}bookings` ADD INDEX `{$prefix}bookings_type_idx` (`booking_type`)");
        $this->db->query("ALTER TABLE `{$prefix}bookings` ADD INDEX `{$prefix}bookings_dorm_idx` (`dorm_id`)");
        $this->db->query("ALTER TABLE `{$prefix}bookings` ADD INDEX `{$prefix}bookings_dropoff_idx` (`dropoff_date`, `dropoff_status`)");
        $this->db->query("ALTER TABLE `{$prefix}bookings` ADD CONSTRAINT `{$prefix}bookings_dorm_fk` FOREIGN KEY (`dorm_id`) REFERENCES `{$prefix}dorms` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
    }

    private function expandBookingItems(): void
    {
        $this->forge->addColumn('booking_items', [
            'item_size_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true, 'after' => 'item_id'],
            'size_name' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'item_name'],
        ]);

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD INDEX `{$prefix}booking_items_size_idx` (`item_size_id`)");
        $this->db->query("ALTER TABLE `{$prefix}booking_items` ADD CONSTRAINT `{$prefix}booking_items_size_fk` FOREIGN KEY (`item_size_id`) REFERENCES `{$prefix}item_sizes` (`id`) ON UPDATE CASCADE ON DELETE SET NULL");
    }

    private function migrateExistingData(): void
    {
        $prefix = $this->db->getPrefix();
        $now    = date('Y-m-d H:i:s');

        $this->db->query("UPDATE `{$prefix}bookings` SET `booking_type` = CASE WHEN LOWER(`service_name`) LIKE '%advantage%' THEN 'summer_advantage' ELSE 'summer_storage' END");
        $this->db->query("UPDATE `{$prefix}bookings` SET `pickup_status` = CASE WHEN `pickup_date` IS NULL THEN 'unscheduled' ELSE 'scheduled' END");
        $this->db->query("INSERT INTO `{$prefix}item_sizes` (`item_id`, `name`, `unit_price`, `is_active`, `created_at`, `updated_at`) SELECT `id`, 'Standard', `unit_price`, `is_active`, " . $this->db->escape($now) . ', ' . $this->db->escape($now) . " FROM `{$prefix}items`");
        $this->db->query("UPDATE `{$prefix}booking_items` bi JOIN `{$prefix}item_sizes` sizes ON sizes.`item_id` = bi.`item_id` AND sizes.`name` = 'Standard' SET bi.`item_size_id` = sizes.`id`, bi.`size_name` = sizes.`name`");
    }
}
