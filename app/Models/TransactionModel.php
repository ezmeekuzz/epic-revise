<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['payment_id', 'booking_id', 'reference', 'type', 'amount', 'currency', 'status', 'description', 'processed_at'];
    protected $useTimestamps = true;
    protected $updatedField = '';
}
