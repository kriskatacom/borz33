<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\ProductImage;

class ProductImageResource
{
    /** @return array<string, mixed> */
    public static function toArray(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'product_variant_id' => $image->product_variant_id,
            'media_file_id' => $image->media_file_id,
            'role' => $image->role,
            'url' => '/' . ltrim($image->path, '/'),
            'original_name' => $image->original_name,
            'mime' => $image->mime,
            'size' => $image->size,
            'alt' => $image->alt,
            'sort_order' => $image->sort_order,
        ];
    }

    /** @param iterable<int, ProductImage> $images */
    public static function collection(iterable $images): array
    {
        $items = [];

        foreach ($images as $image) {
            $items[] = self::toArray($image);
        }

        return $items;
    }
}
