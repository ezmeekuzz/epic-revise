<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class AppSettingModel extends Model
{
    protected $table = 'app_settings';
    protected $primaryKey = 'setting_key';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['setting_key', 'setting_value', 'group_name', 'is_encrypted', 'updated_by', 'updated_at'];
    protected $useTimestamps = false;
}
