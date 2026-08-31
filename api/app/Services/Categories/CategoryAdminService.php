<?php

declare(strict_types=1);

namespace App\Services\Categories;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Resources\CategoryResource;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;

class CategoryAdminService
{
    public function __construct(
        private readonly MediaService $media = new MediaService()
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $categories = $query
            ->with(['parent', 'mediaFile'])
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'categories' => CategoryResource::collection($categories),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id, bool $withTrashed = false): Category
    {
        $query = $withTrashed ? Category::withTrashed() : Category::query();
        $category = $query->find($id);

        if ($category === null) {
            throw new AuthException('Категорията не е намерена.', 404);
        }

        return $category;
    }

    /** @return list<array<string, mixed>> */
    public function tree(): array
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'parent_id', 'sort_order']);

        $items = [];

        foreach ($categories as $category) {
            $items[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'sort_order' => $category->sort_order,
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Category
    {
        $category = new Category();
        $category->forceFill($this->categoryAttributes($data, null, null))->save();

        return $this->fresh($category);
    }

    /** @param array<string, mixed> $data */
    public function update(Category $category, array $data): Category
    {
        $attributes = $this->categoryAttributes($data, (int) $category->id, $category);

        if ($attributes !== []) {
            $category->forceFill($attributes)->save();
        }

        return $this->fresh($category);
    }

    public function delete(Category $category): void
    {
        if ($category->trashed()) {
            throw new AuthException('Категорията вече е изтрита.');
        }

        Capsule::connection()->transaction(function () use ($category): void {
            // Direct children become top-level categories. Their own descendants stay attached,
            // preserving each deeper subtree under its newly promoted main category.
            Category::query()->where('parent_id', $category->id)->update(['parent_id' => null]);
            $category->delete();
        });
    }

    public function restore(int $id): Category
    {
        $category = $this->find($id, true);

        if ($category->deleted_at === null) {
            throw new AuthException('Категорията не е изтрита.');
        }

        $category->restore();

        return $this->fresh($category);
    }

    /** @param list<int> $ids */
    public function bulkSetParent(array $ids, mixed $parentValue): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            throw new ValidationException(['ids' => ['Изберете поне една категория.']]);
        }

        if (count($ids) > 500) {
            throw new ValidationException(['ids' => ['Може да промените до 500 категории наведнъж.']]);
        }

        $parentId = $this->nullableId($parentValue);

        if ($parentValue !== null && $parentValue !== '' && $parentValue !== 0 && $parentValue !== '0' && $parentValue !== 'none' && $parentId === null) {
            throw new ValidationException(['parent_id' => ['Изберете валидна родителска категория или „Без родител“.']]);
        }

        if ($parentId !== null && in_array($parentId, $ids, true)) {
            throw new ValidationException(['parent_id' => ['Избраната родителска категория не може да бъде сред променяните категории.']]);
        }

        return Capsule::connection()->transaction(function () use ($ids, $parentId): int {
            $categories = Category::query()->whereIn('id', $ids)->lockForUpdate()->get();

            if ($categories->count() !== count($ids)) {
                throw new ValidationException(['ids' => ['Някоя от избраните категории не съществува или е изтрита.']]);
            }

            if ($parentId !== null && !Category::query()->whereKey($parentId)->exists()) {
                throw new ValidationException(['parent_id' => ['Избраната родителска категория не съществува или е изтрита.']]);
            }

            foreach ($categories as $category) {
                $this->resolvedParentId($parentId, (int) $category->id);
            }

            return Category::query()->whereIn('id', $ids)->update(['parent_id' => $parentId]);
        });
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? 'all');
        $query = match ($status) {
            'deleted' => Category::onlyTrashed(),
            'inactive' => Category::query()->where('is_active', false),
            'active' => Category::query()->where('is_active', true),
            default => Category::query(),
        };

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            });
        }

        $parent = trim((string) ($filters['parent'] ?? ''));

        if ($parent === 'root' || $parent === 'none' || $parent === '0') {
            $query->whereNull('parent_id');
        } elseif (ctype_digit($parent) && (int) $parent > 0) {
            $query->where('parent_id', (int) $parent);
        }

        return $query;
    }

    /** @param array<string, mixed> $data */
    private function categoryAttributes(array $data, ?int $categoryId, ?Category $existing): array
    {
        if ($existing === null) {
            $slug = trim((string) ($data['slug'] ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) $data['name'], $categoryId);
            }

            return [
                'name' => $data['name'],
                'slug' => $slug,
                'parent_id' => $this->resolvedParentId($data['parent_id'] ?? null, null),
                'media_file_id' => $this->resolvedMediaFileId($data['media_file_id'] ?? null),
                'is_active' => (bool) $data['is_active'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ];
        }

        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = $data['name'];
        }

        if (array_key_exists('slug', $data) || array_key_exists('name', $data)) {
            $slug = trim((string) ($data['slug'] ?? $existing->slug ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) ($data['name'] ?? $existing->name), $categoryId);
            }

            $attributes['slug'] = $slug;
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('parent_id', $data)) {
            $attributes['parent_id'] = $this->resolvedParentId($data['parent_id'], $categoryId);
        }

        if (array_key_exists('media_file_id', $data)) {
            $attributes['media_file_id'] = $this->resolvedMediaFileId($data['media_file_id']);
        }

        if (array_key_exists('sort_order', $data)) {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        return $attributes;
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name, '-', 'bg');
        $base = $base !== '' ? $base : 'category';
        $candidate = $base;
        $suffix = 2;

        while (
            Category::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function resolvedParentId(mixed $value, ?int $categoryId): ?int
    {
        $parentId = $this->nullableId($value);

        if ($parentId === null) {
            return null;
        }

        if ($categoryId !== null && $parentId === $categoryId) {
            throw new ValidationException(['parent_id' => ['Категорията не може да бъде родител на себе си.']]);
        }

        $current = $parentId;
        $guard = 0;

        while ($current !== null && $guard < 64) {
            if ($categoryId !== null && $current === $categoryId) {
                throw new ValidationException(['parent_id' => ['Не може да изберете дете като родител.']]);
            }

            $current = Category::query()->where('id', $current)->value('parent_id');
            $current = is_numeric($current) ? (int) $current : null;
            $guard++;
        }

        return $parentId;
    }

    private function resolvedMediaFileId(mixed $value): ?int
    {
        $mediaId = $this->nullableId($value);

        if ($mediaId === null) {
            return null;
        }

        try {
            $this->media->requireRasterImage($mediaId);
        } catch (ValidationException) {
            throw new ValidationException(['media_file_id' => ['Разрешени са JPEG, PNG и WebP.']]);
        }

        return $mediaId;
    }

    private function nullableId(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function fresh(Category $category): Category
    {
        $fresh = Category::query()->with(['parent', 'mediaFile'])->withCount('products')->find($category->id);

        return $fresh ?? $category;
    }
}
