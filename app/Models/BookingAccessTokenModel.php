<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class BookingAccessTokenModel extends Model
{
    protected $table         = 'booking_access_tokens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = ['booking_id', 'purpose', 'token_hash', 'expires_at', 'used_at', 'created_at'];
    protected $useTimestamps = false;
}
