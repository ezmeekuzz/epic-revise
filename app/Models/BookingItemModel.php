<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class BookingItemModel extends Model
{
    protected $table = 'booking_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['booking_id', 'item_id', 'item_size_id', 'item_name', 'size_name', 'unit_price', 'quantity', 'line_total'];
    protected $useTimestamps = true;
}
