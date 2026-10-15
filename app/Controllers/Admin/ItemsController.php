<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ItemSizeModel;
use App\Models\ItemModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

final class ItemsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        $search = trim((string) $this->request->getGet('q'));
        $status = (string) $this->request->getGet('status');
        $model  = new ItemModel();

        if ($search !== '') {
            $model->like('name', $search)->orLike('description', $search);
        }
        if ($status === 'active' || $status === 'inactive') {
            $model->where('is_active', $status === 'active' ? 1 : 0);
        }

        return view('admin/items/index', [
            'title' => 'Item Masterlist', 'activeAdminPage' => 'items',
            'items' => $model->orderBy('name', 'ASC')->findAll(),
            'filters' => ['q' => $search, 'status' => $status],
        ]);
    }

    public function new(): string
    {
        return $this->formView(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->input();
        $sizes = $this->requestedSizes();
        $legacyPrice = trim((string) $this->request->getPost('unit_price'));
        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($sizes === [] && $legacyPrice === '') {
            return redirect()->back()->withInput()->with('error', 'Add at least one size and its price before creating an item.');
        }
        foreach ($sizes as $size) {
            if ($size['name'] === '' || mb_strlen($size['name']) > 80 || ! is_numeric($size['unit_price']) || (float) $size['unit_price'] < 0) {
                return redirect()->back()->withInput()->with('error', 'Each item size needs a name and a non-negative price.');
            }
        }
        $names = array_map(static fn (array $size): string => strtolower($size['name']), $sizes);
        if (count($names) !== count(array_unique($names))) {
            return redirect()->back()->withInput()->with('error', 'Item size names must be unique.');
        }

        try {
            $db = db_connect();
            $db->transStart();
            $itemId = (int) (new ItemModel())->insert($data, true);
            if ($itemId > 0) {
                if ($sizes === []) {
                    // Legacy API clients may still send a base price. It is used
                    // only to seed the first size; new forms always submit sizes.
                    $sizes[] = ['name' => 'Standard', 'dimensions' => null, 'unit_price' => number_format(max(0, (float) $legacyPrice), 2, '.', ''), 'is_active' => 1];
                }
                foreach ($sizes as $size) {
                    (new ItemSizeModel())->insert(['item_id' => $itemId, ...$size]);
                }
            }
            $db->transComplete();
            if (! $db->transStatus()) {
                throw new DatabaseException('Unable to save item sizes.');
            }
        } catch (DatabaseException $exception) {
            log_message('error', 'Item create failed: {message}', ['message' => $exception->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'The item and its size prices could not be created. Please review the values.');
        }

        return redirect()->route('admin.items')->with('success', 'Storage item created.');
    }

    public function edit(int $id): string
    {
        return $this->formView($this->find($id));
    }

    public function sizes(int $itemId): string
    {
        $item = $this->find($itemId);
        return view('admin/items/sizes', ['title' => $item['name'] . ' Sizes', 'activeAdminPage' => 'items', 'item' => $item, 'sizes' => (new ItemSizeModel())->where('item_id', $itemId)->orderBy('name', 'ASC')->findAll()]);
    }

    public function createSize(int $itemId): RedirectResponse
    {
        $item = $this->find($itemId);
        $data = $this->sizeInput($itemId);
        if (! $this->validateData($data, $this->sizeRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ((new ItemSizeModel())->where('item_id', $itemId)->where('name', $data['name'])->first() !== null) {
            return redirect()->back()->withInput()->with('error', 'That size name already exists for this item.');
        }
        try {
            (new ItemSizeModel())->insert($data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The item size could not be created. Please review the values.');
        }
        return redirect()->route('admin.items.sizes', [$itemId])->with('success', 'Item size created for ' . $item['name'] . '.');
    }

    public function updateSize(int $itemId, int $sizeId): RedirectResponse
    {
        $this->find($itemId);
        $sizes = new ItemSizeModel();
        if ($sizes->where('id', $sizeId)->where('item_id', $itemId)->first() === null) {
            throw PageNotFoundException::forPageNotFound('Item size not found.');
        }
        $data = $this->sizeInput($itemId);
        if (! $this->validateData($data, $this->sizeRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if ($sizes->where('item_id', $itemId)->where('name', $data['name'])->where('id !=', $sizeId)->first() !== null) {
            return redirect()->back()->withInput()->with('error', 'That size name already exists for this item.');
        }
        try {
            $sizes->update($sizeId, $data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The item size could not be updated. Please review the values.');
        }
        return redirect()->route('admin.items.sizes', [$itemId])->with('success', 'Item size updated.');
    }

    public function deleteSize(int $itemId, int $sizeId): RedirectResponse
    {
        $this->find($itemId);
        $sizes = new ItemSizeModel();
        if ($sizes->where('id', $sizeId)->where('item_id', $itemId)->first() === null) {
            throw PageNotFoundException::forPageNotFound('Item size not found.');
        }
        $sizes->delete($sizeId);
        return redirect()->route('admin.items.sizes', [$itemId])->with('success', 'Item size archived. Existing booking snapshots are preserved.');
    }

    public function update(int $id): RedirectResponse
    {
        $this->find($id);
        $data = $this->input();
        if (! $this->validateData($data, $this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            (new ItemModel())->update($id, $data);
        } catch (DatabaseException) {
            return redirect()->back()->withInput()->with('error', 'The item could not be updated. Please review the values.');
        }

        return redirect()->route('admin.items')->with('success', 'Storage item updated.');
    }

    public function delete(int $id): RedirectResponse
    {
        $this->find($id);
        (new ItemModel())->delete($id);

        return redirect()->route('admin.items')->with('success', 'Storage item archived. Existing booking snapshots were preserved.');
    }

    /** @return array<string, string|int|null> */
    private function input(): array
    {
        $data = [
            'name' => trim((string) $this->request->getPost('name')),
            'description' => trim((string) $this->request->getPost('description')),
            'is_active' => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];
        // Accept legacy API payloads during the transition, while the admin UI
        // no longer exposes these item-level fields.
        $legacySku = strtoupper(trim((string) $this->request->getPost('sku')));
        $legacyCategory = trim((string) $this->request->getPost('category'));
        $legacyPrice = trim((string) $this->request->getPost('unit_price'));
        if ($legacySku !== '') $data['sku'] = $legacySku;
        if ($legacyCategory !== '') $data['category'] = $legacyCategory;
        if ($legacyPrice !== '') $data['unit_price'] = number_format(max(0, (float) $legacyPrice), 2, '.', '');
        return $data;
    }

    /** @return array<string, string> */
    private function rules(int $id = 0): array
    {
        return [
            'name' => 'required|min_length[2]|max_length[120]',
            'description' => 'permit_empty|max_length[5000]', 'is_active' => 'required|in_list[0,1]',
        ];
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $item = (new ItemModel())->find($id);
        if ($item === null) {
            throw PageNotFoundException::forPageNotFound('Item not found.');
        }
        return $item;
    }

    /** @return array<string, string|int|null> */
    private function sizeInput(int $itemId): array
    {
        return ['item_id' => $itemId, 'name' => trim((string) $this->request->getPost('name')), 'dimensions' => trim((string) $this->request->getPost('dimensions')) ?: null, 'unit_price' => number_format(max(0, (float) $this->request->getPost('unit_price')), 2, '.', ''), 'is_active' => $this->request->getPost('is_active') === '1' ? 1 : 0];
    }

    /** @return array<string, string> */
    private function sizeRules(): array
    {
        return ['name' => 'required|min_length[1]|max_length[80]', 'dimensions' => 'permit_empty|max_length[120]', 'unit_price' => 'required|decimal|greater_than_equal_to[0]', 'is_active' => 'required|in_list[0,1]'];
    }

    /** @return list<array{name:string,dimensions:?string,unit_price:string,is_active:int}> */
    private function requestedSizes(): array
    {
        $names = $this->request->getPost('size_name');
        $prices = $this->request->getPost('size_price');
        $dimensions = $this->request->getPost('size_dimensions');
        if (! is_array($names) || ! is_array($prices)) {
            return [];
        }
        $result = [];
        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            $price = trim((string) ($prices[$index] ?? ''));
            if ($name === '' && $price === '') {
                continue;
            }
            $result[] = ['name' => $name, 'dimensions' => trim((string) ($dimensions[$index] ?? '')) ?: null, 'unit_price' => number_format(max(0, (float) $price), 2, '.', ''), 'is_active' => 1];
        }
        return $result;
    }

    /** @param array<string, mixed>|null $item */
    private function formView(?array $item): string
    {
        return view('admin/items/form', ['title' => $item === null ? 'Add Item' : 'Edit Item', 'activeAdminPage' => 'items', 'item' => $item]);
    }
}
