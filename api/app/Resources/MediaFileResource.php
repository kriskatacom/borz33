<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\MediaFile;

class MediaFileResource
{
    /** @return array<string, mixed> */
    public static function toArray(MediaFile $file): array
    {
        return [
            'id' => $file->id,
            'url' => '/' . ltrim($file->path, '/'),
            'original_name' => $file->original_name,
            'mime' => $file->mime,
            'extension' => $file->extension,
            'kind' => $file->kind,
            'size' => $file->size,
            'alt' => $file->alt,
            'uploaded_by' => $file->uploaded_by,
            'created_at' => $file->created_at?->toIso8601String(),
            'updated_at' => $file->updated_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, MediaFile> $files */
    public static function collection(iterable $files): array
    {
        $items = [];

        foreach ($files as $file) {
            $items[] = self::toArray($file);
        }

        return $items;
    }
}
