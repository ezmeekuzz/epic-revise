<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Libraries\CustomerDetails;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

final class UsersController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $role   = (string) $this->request->getGet('role');
        $status = (string) $this->request->getGet('status');
        $users  = (new UserModel())->where('role', 'customer');

        if ($search !== '') {
            $users->groupStart()->like('name', $search)->orLike('email', $search)->groupEnd();
        }

        if (in_array($status, ['active', 'inactive', 'pending'], true)) {
            $users->where('status', $status);
        }

        return view('admin/users/index', [
            'title'           => 'Manage Users',
            'activeAdminPage' => 'users',
            'users'           => $users->orderBy('created_at', 'DESC')->findAll(),
            'filters'         => ['q' => $search, 'role' => $role, 'status' => $status],
            'currentUserId'   => (int) session()->get('admin_user_id'),
        ]);
    }

    public function new(): string
    {
        return view('admin/users/form', [
            'title'           => 'Add User',
            'activeAdminPage' => 'users',
            'user'            => null,
        ]);
    }

    public function create(): RedirectResponse
    {
        $data = $this->userInput();

        if (! $this->validateData($data, $this->rules(true))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            (new UserModel())->insert([
                ...CustomerDetails::input($this->request),
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'          => 'customer',
                'status'        => $data['status'],
            ]);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The user could not be created. The email address may already exist.');
        }

        return redirect()->route('admin.users')->with('success', 'User account created successfully.');
    }

    public function edit(int $id): string
    {
        $user = $this->findUser($id);

        return view('admin/users/form', [
            'title'           => 'Edit User',
            'activeAdminPage' => 'users',
            'user'            => $user,
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $users = new UserModel();
        $user  = $this->findUser($id);

        if ($id === (int) session()->get('admin_user_id')) {
            return redirect()->route('admin.account')->with('error', 'Use Admin Account to update your own profile and password.');
        }

        $data = $this->userInput();
        if (! $this->validateData($data, $this->rules(false, $id, $data['password'] !== ''))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $update = [
            ...CustomerDetails::input($this->request),
            'name'   => $data['name'],
            'email'  => $data['email'],
            'role'   => 'customer',
            'status' => $data['status'],
        ];

        if ($data['password'] !== '') {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        try {
            $users->update($id, $update);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The user could not be updated. The email address may already exist.');
        }

        return redirect()->route('admin.users')->with('success', 'User account updated successfully.');
    }

    public function delete(int $id): RedirectResponse
    {
        if ($id === (int) session()->get('admin_user_id')) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own signed-in account.');
        }

        $users = new UserModel();
        $user  = $this->findUser($id);

        $users->delete($id);

        return redirect()->route('admin.users')->with('success', 'User account removed.');
    }

    /** @return array{name: string, email: string, password: string, password_confirm: string, role: string, status: string} */
    private function userInput(): array
    {
        return [
            ...CustomerDetails::input($this->request),
            'name'             => trim((string) $this->request->getPost('name')),
            'email'            => mb_strtolower(trim((string) $this->request->getPost('email'))),
            'password'         => (string) $this->request->getPost('password'),
            'password_confirm' => (string) $this->request->getPost('password_confirm'),
            'role'             => 'customer',
            'status'           => (string) $this->request->getPost('status'),
        ];
    }

    /** @return array<string, string> */
    private function rules(bool $creating, int $id = 0, bool $passwordProvided = true): array
    {
        $passwordRequired = $creating || $passwordProvided;
        $passwordRule     = $passwordRequired ? 'required|min_length[12]|max_length[72]' : 'permit_empty';

        return [
            ...CustomerDetails::rules(),
            'name'             => 'required|min_length[2]|max_length[100]',
            'email'            => 'required|valid_email|max_length[254]|is_unique[users.email,id,' . $id . ']',
            'password'         => $passwordRule,
            'password_confirm' => ($passwordRequired ? 'required' : 'permit_empty') . '|matches[password]',
            'role'             => 'required|in_list[customer]',
            'status'           => 'required|in_list[active,inactive,pending]',
        ];
    }

    /** @return array<string, mixed> */
    private function findUser(int $id): array
    {
        $user = (new UserModel())->where('id', $id)->where('role', 'customer')->first();
        if ($user === null) {
            throw PageNotFoundException::forPageNotFound('User not found.');
        }

        return $user;
    }
}
