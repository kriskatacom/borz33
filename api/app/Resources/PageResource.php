<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Page;

class PageResource
{
    /** @return array<string, mixed> */
    public static function toAdminArray(Page $page): array
    {
        $page->loadMissing(['fields.mediaFile', 'parent']);

        return [
            ...self::toAdminListArray($page),
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'fields' => PageFieldResource::collection($page->fields),
        ];
    }

    /** @return array<string, mixed> */
    public static function toAdminListArray(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'parent_id' => $page->parent_id,
            'parent' => $page->parent ? [
                'id' => $page->parent->id,
                'title' => $page->parent->title,
                'slug' => $page->parent->slug,
            ] : null,
            'is_active' => $page->is_active,
            'sort_order' => $page->sort_order,
            'fields_count' => (int) ($page->fields_count ?? $page->fields()->count()),
            'created_at' => $page->created_at?->toIso8601String(),
            'updated_at' => $page->updated_at?->toIso8601String(),
            'deleted_at' => $page->deleted_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, Page> $pages */
    public static function collection(iterable $pages): array
    {
        $items = [];

        foreach ($pages as $page) {
            $items[] = self::toAdminListArray($page);
        }

        return $items;
    }
}
