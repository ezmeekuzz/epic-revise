<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class ContactMessageModel extends Model
{
    protected $table = 'contact_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['first_name', 'last_name', 'phone', 'email', 'message'];
    protected $useTimestamps = true;
    protected $updatedField = '';
}
