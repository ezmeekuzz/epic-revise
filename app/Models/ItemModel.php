<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class ItemModel extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    // Pricing belongs to item_sizes; legacy fields remain readable for old API
    // payloads but are not exposed by the current admin UI.
    protected $allowedFields = ['name', 'description', 'is_active', 'sku', 'category', 'unit_price'];
    protected $useTimestamps = true;
    protected $deletedField = 'deleted_at';
}
