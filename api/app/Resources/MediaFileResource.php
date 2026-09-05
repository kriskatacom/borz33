<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\MediaFile;
use App\Services\Products\ProductImageStorage;

class MediaFileResource
{
    /** @return array<string, mixed> */
    public static function toArray(MediaFile $file): array
    {
        [$width, $height] = self::dimensions($file);

        return [
            'id' => $file->id,
            'url' => StorageUrl::forPath($file->path),
            'original_name' => $file->original_name,
            'mime' => $file->mime,
            'extension' => $file->extension,
            'kind' => $file->kind,
            'size' => $file->size,
            'original_size' => $file->original_size,
            'alt' => $file->alt,
            'title' => $file->title,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $file->uploaded_by,
            'created_at' => $file->created_at?->toIso8601String(),
            'updated_at' => $file->updated_at?->toIso8601String(),
        ];
    }

    /** @return array{0: int|null, 1: int|null} */
    private static function dimensions(MediaFile $file): array
    {
        if (!$file->isImage()) {
            return [null, null];
        }

        $width = $file->width !== null ? (int) $file->width : null;
        $height = $file->height !== null ? (int) $file->height : null;
        if ($width !== null && $height !== null) {
            return [$width, $height];
        }

        $path = (new ProductImageStorage())->absolutePath($file->path);
        if (!is_file($path)) {
            return [$width, $height];
        }

        $size = @getimagesize($path);
        if (!is_array($size) || !isset($size[0], $size[1])) {
            return [$width, $height];
        }

        return [(int) $size[0], (int) $size[1]];
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
