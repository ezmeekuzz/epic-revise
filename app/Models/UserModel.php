<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'email',
        'student_id',
        'phone_number',
        'street_number',
        'street_name',
        'dorm_id',
        'dorm_room_number',
        'parent_phone_number',
        'parent_email_address',
        'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip',
        'password_hash',
        'role',
        'status',
        'email_verified_at',
        'last_login_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $beforeInsert  = ['normalize'];
    protected $beforeUpdate  = ['normalize'];

    /** @param array<string, mixed> $data */
    protected function normalize(array $data): array
    {
        if (isset($data['data']['name'])) {
            $data['data']['name'] = trim((string) $data['data']['name']);
        }

        if (isset($data['data']['email'])) {
            $data['data']['email'] = mb_strtolower(trim((string) $data['data']['email']));
        }

        return $data;
    }

    public function activeAdminCount(): int
    {
        return $this->where('role', 'admin')->where('status', 'active')->countAllResults();
    }
}
