<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

final class EmailVerificationTokenModel extends Model
{
    protected $table         = 'email_verification_tokens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'token_hash',
        'redirect_path',
        'expires_at',
        'used_at',
        'created_at',
    ];
    protected $useTimestamps = false;

    public function invalidateForUser(int $userId, int $exceptId = 0): void
    {
        $builder = $this->builder()->where('user_id', $userId)->where('used_at', null);
        if ($exceptId > 0) {
            $builder->where('id !=', $exceptId);
        }

        $builder->update(['used_at' => date('Y-m-d H:i:s')]);
    }
}
