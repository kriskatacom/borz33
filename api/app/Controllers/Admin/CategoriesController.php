<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\CategoryResource;
use App\Services\Categories\CategoryAdminService;
use App\Validation\CategoryValidator;

class CategoriesController extends Controller
{
    public function __construct(
        private readonly CategoryAdminService $categories = new CategoryAdminService(),
        private readonly CategoryValidator $validator = new CategoryValidator()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->categories->paginate(Request::query()), 'Списък с категории.');
    }

    public function tree(): never
    {
        $this->ok(['categories' => $this->categories->tree()], 'Дърво с категории.');
    }

    public function bulkParent(): never
    {
        $input = Request::input();
        $rawIds = is_array($input['ids'] ?? null) ? $input['ids'] : [];
        $ids = array_values(array_filter(array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $rawIds), static fn (int $id): bool => $id > 0));
        $updated = $this->categories->bulkSetParent($ids, $input['parent_id'] ?? null);

        $this->ok(['updated' => $updated], $updated === 1 ? 'Родителят на категорията е обновен.' : 'Родителят на категориите е обновен.');
    }

    public function show(string $id): never
    {
        $category = $this->categories->find($this->id($id), true);

        $this->ok(['category' => CategoryResource::toAdminArray($category)]);
    }

    public function store(): never
    {
        $payload = $this->validator->validate(Request::input());
        $category = $this->categories->create($payload);

        $this->created(['category' => CategoryResource::toAdminArray($category)], 'Категорията е създадена.');
    }

    public function update(string $id): never
    {
        $category = $this->categories->find($this->id($id));
        $payload = $this->validator->validate(Request::input(), $category->id);
        $category = $this->categories->update($category, $payload);

        $this->ok(['category' => CategoryResource::toAdminArray($category)], 'Категорията е обновена.');
    }

    public function destroy(string $id): never
    {
        $this->categories->delete($this->categories->find($this->id($id)));

        $this->ok([], 'Категорията е изтрита.');
    }

    public function restore(string $id): never
    {
        $category = $this->categories->restore($this->id($id));

        $this->ok(['category' => CategoryResource::toAdminArray($category)], 'Категорията е възстановена.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Категорията не е намерена.', 404);
        }

        return (int) $id;
    }
}
