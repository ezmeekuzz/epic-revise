<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DormModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

final class DormsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $model = new DormModel();
        if ($search !== '') {
            $model->groupStart()->like('name', $search)->orLike('code', $search)->groupEnd();
        }

        return view('admin/dorms/index', ['title' => 'Dorms', 'activeAdminPage' => 'dorms', 'dorms' => $model->orderBy('name', 'ASC')->findAll(), 'search' => $search]);
    }

    public function new(): string
    {
        return $this->formView(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->input();
        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            (new DormModel())->insert($data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The dorm could not be created. Its name or code may already exist.');
        }
        return redirect()->route('admin.dorms')->with('success', 'Dorm created.');
    }

    public function edit(int $id): string
    {
        return $this->formView($this->find($id));
    }

    public function update(int $id): RedirectResponse
    {
        $this->find($id);
        $data = $this->input();
        if (! $this->validateData($data, $this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        try {
            (new DormModel())->update($id, $data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The dorm could not be updated. Its name or code may already exist.');
        }
        return redirect()->route('admin.dorms')->with('success', 'Dorm updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        $this->find($id);
        (new DormModel())->delete($id);
        return redirect()->route('admin.dorms')->with('success', 'Dorm archived. Existing bookings retain their saved dorm reference.');
    }

    /** @return array<string, string|int> */
    private function input(): array
    {
        return ['name' => trim((string) $this->request->getPost('name')), 'code' => strtoupper(trim((string) $this->request->getPost('code'))) ?: null, 'address' => trim((string) $this->request->getPost('address')) ?: null, 'pickup_notes' => trim((string) $this->request->getPost('pickup_notes')) ?: null, 'is_active' => $this->request->getPost('is_active') === '1' ? 1 : 0];
    }

    /** @return array<string, string> */
    private function rules(int $id = 0): array
    {
        return ['name' => 'required|min_length[2]|max_length[120]|is_unique[dorms.name,id,' . $id . ']', 'code' => 'permit_empty|alpha_dash|max_length[40]|is_unique[dorms.code,id,' . $id . ']', 'address' => 'permit_empty|max_length[255]', 'pickup_notes' => 'permit_empty|max_length[5000]', 'is_active' => 'required|in_list[0,1]'];
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $dorm = (new DormModel())->find($id);
        if ($dorm === null) {
            throw PageNotFoundException::forPageNotFound('Dorm not found.');
        }
        return $dorm;
    }

    /** @param array<string, mixed>|null $dorm */
    private function formView(?array $dorm): string
    {
        return view('admin/dorms/form', ['title' => $dorm === null ? 'Add Dorm' : 'Edit Dorm', 'activeAdminPage' => 'dorms', 'dorm' => $dorm]);
    }
}
