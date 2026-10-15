<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class ItemSizeModel extends Model
{
    protected $table = 'item_sizes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['item_id', 'name', 'dimensions', 'unit_price', 'is_active'];
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
}
