<?php

declare(strict_types=1);

namespace App\Commands;

use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

final class CreateAdmin extends BaseCommand
{
    protected $group       = 'Epic';
    protected $name        = 'admin:create';
    protected $description = 'Creates a new Epic administrator account.';
    protected $usage       = 'admin:create --name "Full Name" --email admin@example.com';
    protected $options     = [
        '--name'  => 'Administrator full name. You will be prompted when omitted.',
        '--email' => 'Administrator email address. You will be prompted when omitted.',
    ];

    public function run(array $params): void
    {
        $name     = trim((string) (CLI::getOption('name') ?: CLI::prompt('Administrator name')));
        $email    = mb_strtolower(trim((string) (CLI::getOption('email') ?: CLI::prompt('Administrator email'))));
        $password = (string) getenv('EPIC_ADMIN_PASSWORD');

        $validation = service('validation');
        $validation->setRules([
            'name'     => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[12]|max_length[72]',
        ]);

        if (! $validation->run(['name' => $name, 'email' => $email, 'password' => $password])) {
            foreach ($validation->getErrors() as $error) {
                CLI::error($error);
            }

            if ($password === '') {
                CLI::write('Set EPIC_ADMIN_PASSWORD in the current shell, then run the command again.', 'yellow');
            }

            return;
        }

        $users = new UserModel();
        if ($users->where('email', $email)->first() !== null) {
            CLI::error('An account already exists for that email address.');

            return;
        }

        $users->insert([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        CLI::write('Administrator created successfully.', 'green');
    }
}
