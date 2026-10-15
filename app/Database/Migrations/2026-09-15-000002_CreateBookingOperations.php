<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

final class CreateBookingOperations extends Migration
{
    public function up(): void
    {
        $this->createItems();
        $this->createBookings();
        $this->createBookingItems();
        $this->createPayments();
        $this->createTransactions();
        $this->createSettings();
        $this->seedSettings();
    }

    public function down(): void
    {
        $this->forge->dropTable('app_settings', true);
        $this->forge->dropTable('transactions', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('booking_items', true);
        $this->forge->dropTable('bookings', true);
        $this->forge->dropTable('items', true);
    }

    private function createItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'category' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'Storage'],
            'description' => ['type' => 'TEXT', 'null' => true],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey(['is_active', 'category']);
        $this->forge->createTable('items');
    }

    private function createBookings(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'reference_code' => ['type' => 'VARCHAR', 'constraint' => 32],
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'customer_email' => ['type' => 'VARCHAR', 'constraint' => 254],
            'customer_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'student_id' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'service_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'pickup_date' => ['type' => 'DATE', 'null' => true],
            'pickup_time' => ['type' => 'TIME', 'null' => true],
            'pickup_location' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true],
            'room_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'payment_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unpaid'],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'admin_notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference_code');
        $this->forge->addKey(['status', 'pickup_date']);
        $this->forge->addKey(['payment_status', 'created_at']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('bookings');
    }

    private function createBookingItems(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'item_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'unit_price' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'quantity' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
            'line_total' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('booking_id');
        $this->forge->addKey('item_id');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('item_id', 'items', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('booking_items');
    }

    private function createPayments(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 50],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'manual'],
            'method' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'manual'],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'currency' => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'pending'],
            'external_reference' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('booking_id');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('payments');
    }

    private function createTransactions(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'payment_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'booking_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 60],
            'type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'currency' => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'USD'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['type', 'status', 'created_at']);
        $this->forge->addKey('payment_id');
        $this->forge->addKey('booking_id');
        $this->forge->addForeignKey('payment_id', 'payments', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('transactions');
    }

    private function createSettings(): void
    {
        $this->forge->addField([
            'setting_key' => ['type' => 'VARCHAR', 'constraint' => 100],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'group_name' => ['type' => 'VARCHAR', 'constraint' => 40],
            'is_encrypted' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'updated_by' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('setting_key');
        $this->forge->addKey('group_name');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('app_settings');
    }

    private function seedSettings(): void
    {
        $defaults = [
            'business_name' => ['Epic Storage Solutions', 'general', 0],
            'support_email' => ['support@epicstoragehpu.com', 'general', 0],
            'business_phone' => ['336.549.5216', 'general', 0],
            'business_timezone' => ['America/New_York', 'general', 0],
            'bookings_enabled' => ['1', 'booking', 0],
            'booking_unavailable_message' => ['Online booking is temporarily unavailable. Please contact our team for assistance.', 'booking', 0],
            'daily_booking_capacity' => ['25', 'booking', 0],
            'minimum_advance_hours' => ['24', 'booking', 0],
            'pickup_start_time' => ['09:00', 'booking', 0],
            'pickup_end_time' => ['17:00', 'booking', 0],
            'slot_duration_minutes' => ['30', 'booking', 0],
            'blackout_dates' => ['', 'booking', 0],
            'smtp_enabled' => ['0', 'email', 0],
            'smtp_host' => ['', 'email', 0],
            'smtp_port' => ['587', 'email', 0],
            'smtp_username' => ['', 'email', 0],
            'smtp_password' => ['', 'email', 1],
            'smtp_crypto' => ['tls', 'email', 0],
            'email_from_address' => ['support@epicstoragehpu.com', 'email', 0],
            'email_from_name' => ['Epic Storage Solutions', 'email', 0],
            'payments_enabled' => ['0', 'payment', 0],
            'payment_provider' => ['square', 'payment', 0],
            'payment_mode' => ['sandbox', 'payment', 0],
            'payment_currency' => ['USD', 'payment', 0],
            'payment_public_key' => ['', 'payment', 0],
            'payment_secret_key' => ['', 'payment', 1],
            'payment_webhook_secret' => ['', 'payment', 1],
            'square_application_id' => ['', 'payment', 0],
            'square_access_token' => ['', 'payment', 1],
            'square_location_id' => ['', 'payment', 0],
            'square_webhook_signature_key' => ['', 'payment', 1],
        ];
        $now  = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($defaults as $key => [$value, $group, $encrypted]) {
            $rows[] = ['setting_key' => $key, 'setting_value' => $value, 'group_name' => $group, 'is_encrypted' => $encrypted, 'updated_at' => $now];
        }

        $this->db->table('app_settings')->insertBatch($rows);
    }
}
