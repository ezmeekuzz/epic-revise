<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class BookingModel extends Model
{
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['terms_snapshot', 'terms_accepted_at', 'reference_code', 'user_id', 'customer_name', 'customer_email', 'customer_phone', 'student_id', 'dorm_id', 'service_name', 'booking_type', 'pickup_date', 'pickup_time', 'pickup_location', 'room_number', 'dropoff_date', 'dropoff_time', 'dropoff_location', 'dropoff_room_number', 'pickup_status', 'dropoff_status', 'status', 'payment_status', 'subtotal', 'discount_amount', 'total_amount', 'admin_notes', 'ordernumber', 'card_holder_name', 'booking_date', 'base_price', 'additional_box_quantity', 'additional_box_amount', 'addtl_box_total_amount', 'study_abroad_additional_storage_price', 'is_study_abroad', 'is_summer_school', 'summer_school_fee', 'notes', 'row_in_warehouse'];
    protected $useTimestamps = true;
}
