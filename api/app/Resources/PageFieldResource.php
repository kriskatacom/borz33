<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\PageField;

class PageFieldResource
{
    /** @return array<string, mixed> */
    public static function toArray(PageField $field): array
    {
        $field->loadMissing('mediaFile');

        return [
            'id' => $field->id,
            'name' => $field->name,
            'slug' => $field->slug,
            'field_type' => $field->field_type,
            'value' => $field->isFile() ? null : $field->value,
            'media_file_id' => $field->media_file_id,
            'media' => $field->mediaFile ? MediaFileResource::toArray($field->mediaFile) : null,
            'is_required' => $field->is_required,
            'sort_order' => $field->sort_order,
        ];
    }

    /** @param iterable<int, PageField> $fields */
    public static function collection(iterable $fields): array
    {
        $items = [];

        foreach ($fields as $field) {
            $items[] = self::toArray($field);
        }

        return $items;
    }
}
