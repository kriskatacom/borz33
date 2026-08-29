<?php

declare(strict_types=1);

namespace Store\Services;

use App\Models\Product;
use App\Resources\ProductImageResource;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch
{
    public const LIMIT = 8;

    /** @return array{featured: bool, products: list<array<string, mixed>>} */
    public static function suggest(string $query, int $limit = self::LIMIT): array
    {
        $query = trim($query);
        $featured = $query === '';

        $builder = Product::query()
            ->where('is_active', true)
            ->with('frontImage');

        if ($featured) {
            $builder->inRandomOrder();
        } else {
            $like = '%' . addcslashes(mb_substr($query, 0, 80), '%_\\') . '%';
            $builder->where(static function (Builder $inner) use ($like): void {
                $inner
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhereHas('variants', static function (Builder $variants) use ($like): void {
                        $variants->where('sku', 'like', $like);
                    });
            })->orderBy('name')->orderBy('id');
        }

        $products = $builder->limit($limit)->get()->map(static fn (Product $product): array => self::toArray($product))->values()->all();

        return [
            'featured' => $featured,
            'products' => $products,
        ];
    }

    /** @return array<string, mixed> */
    private static function toArray(Product $product): array
    {
        $price = (float) $product->price;
        $compare = $product->compare_at_price !== null ? (float) $product->compare_at_price : null;
        $onSale = $compare !== null && $compare > $price;
        $image = $product->frontImage;

        return [
            'id' => (int) $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'url' => '/products/' . $product->slug,
            'sku' => $product->sku,
            'price' => $product->price,
            'compare_at_price' => $onSale ? $product->compare_at_price : null,
            'savings' => $onSale ? number_format($compare - $price, 2, '.', '') : null,
            'on_sale' => $onSale,
            'image' => $image !== null ? ProductImageResource::toArray($image)['url'] : null,
            'image_alt' => $image?->alt ?: $product->name,
        ];
    }
}
