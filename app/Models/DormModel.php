<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class DormModel extends Model
{
    protected $table = 'dorms';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'code', 'address', 'pickup_notes', 'is_active'];
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
}
