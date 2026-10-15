<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['booking_id', 'user_id', 'reference', 'provider', 'method', 'amount', 'currency', 'status', 'external_reference', 'paid_at', 'notes'];
    protected $useTimestamps = true;
}
