<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\ProductColorSuggestion;

final class ProductColorSuggestionResource
{
    /** @return array<string, mixed> */
    public static function toArray(ProductColorSuggestion $suggestion): array
    {
        return [
            'id' => $suggestion->id,
            'product_id' => $suggestion->product_id,
            'product_variant_id' => $suggestion->product_variant_id,
            'product_image_id' => $suggestion->product_image_id,
            'color_name_bg' => $suggestion->color_name_bg,
            'color_hex' => $suggestion->color_hex,
            'confidence' => $suggestion->confidence,
            'is_multicolor' => (bool) $suggestion->is_multicolor,
            'model' => $suggestion->model,
            'created_at' => $suggestion->created_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, ProductColorSuggestion> $items */
    public static function collection(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) $result[] = self::toArray($item);
        return $result;
    }
}
