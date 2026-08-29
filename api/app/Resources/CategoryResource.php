<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Category;

class CategoryResource
{
    /** @return array<string, mixed> */
    public static function toAdminArray(Category $category): array
    {
        $category->loadMissing(['parent', 'mediaFile']);

        return self::toAdminListArray($category);
    }

    /** @return array<string, mixed> */
    public static function toAdminListArray(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'parent' => $category->parent ? [
                'id' => $category->parent->id,
                'name' => $category->parent->name,
                'slug' => $category->parent->slug,
            ] : null,
            'media_file_id' => $category->media_file_id,
            'media' => $category->mediaFile ? MediaFileResource::toArray($category->mediaFile) : null,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'products_count' => (int) ($category->products_count ?? $category->products()->count()),
            'created_at' => $category->created_at?->toIso8601String(),
            'updated_at' => $category->updated_at?->toIso8601String(),
            'deleted_at' => $category->deleted_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, Category> $categories */
    public static function collection(iterable $categories): array
    {
        $items = [];

        foreach ($categories as $category) {
            $items[] = self::toAdminListArray($category);
        }

        return $items;
    }
}
