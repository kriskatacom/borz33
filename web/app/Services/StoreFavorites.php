<?php

declare(strict_types=1);

namespace Store\Services;

use App\Core\Auth;
use App\Models\Product;
use App\Resources\ProductImageResource;
use Illuminate\Support\Collection;

final class StoreFavorites
{
    /** @return list<int> */
    public static function ids(): array
    {
        $user = Auth::user();

        if ($user !== null) {
            self::mergeGuestFavorites();

            return $user->favoriteProducts()->pluck('products.id')->map(static fn ($id): int => (int) $id)->all();
        }

        return self::sessionIds();
    }

    public static function count(): int
    {
        return count(self::ids());
    }

    public static function contains(int $productId): bool
    {
        return in_array($productId, self::ids(), true);
    }

    public static function toggle(int $productId): bool
    {
        $product = Product::query()->where('is_active', true)->find($productId);

        if (!$product instanceof Product) {
            throw new \InvalidArgumentException('Продуктът не е намерен.');
        }

        $user = Auth::user();

        if ($user !== null) {
            self::mergeGuestFavorites();
            $favorite = $user->favoriteProducts()->whereKey($productId)->exists();

            if ($favorite) {
                $user->favoriteProducts()->detach($productId);
                return false;
            }

            $user->favoriteProducts()->attach($productId, ['created_at' => date('Y-m-d H:i:s')]);
            return true;
        }

        $ids = self::sessionIds();
        $favorite = in_array($productId, $ids, true);
        $_SESSION['store_favorites'] = $favorite
            ? array_values(array_filter($ids, static fn (int $id): bool => $id !== $productId))
            : array_values(array_unique([$productId, ...$ids]));

        return !$favorite;
    }

    /** @return Collection<int, Product> */
    public static function products(): Collection
    {
        $ids = self::ids();

        if ($ids === []) {
            return new Collection();
        }

        $order = array_flip($ids);

        return Product::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->with('frontImage')
            ->get()
            ->sortBy(static fn (Product $product): int => $order[(int) $product->id] ?? PHP_INT_MAX)
            ->values();
    }

    /** @return array<string, mixed> */
    public static function productCard(Product $product): array
    {
        $image = $product->frontImage;
        $price = (float) $product->price;
        $compare = $product->compare_at_price !== null ? (float) $product->compare_at_price : 0.0;
        $onSale = $compare > $price && $price > 0;

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'description' => trim(strip_tags((string) ($product->short_description ?: $product->description))),
            'href' => '/products/' . $product->slug,
            'price' => ProductPage::money($product->price),
            'comparePrice' => $onSale ? ProductPage::money($compare) : null,
            'discountPercent' => $onSale ? max(1, (int) round((1 - ($price / $compare)) * 100)) : null,
            'weight' => ProductPage::weight($product->weight_grams),
            'image' => $image !== null ? (string) ProductImageResource::toArray($image)['url'] : null,
            'alt' => $image?->alt ?: $product->name,
        ];
    }

    /** @return list<int> */
    private static function sessionIds(): array
    {
        $raw = $_SESSION['store_favorites'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)));
    }

    private static function mergeGuestFavorites(): void
    {
        $ids = self::sessionIds();
        $user = Auth::user();

        if ($user === null || $ids === []) {
            return;
        }

        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach ($ids as $id) {
            $rows[$id] = ['created_at' => $now];
        }

        $user->favoriteProducts()->syncWithoutDetaching($rows);
        unset($_SESSION['store_favorites']);
    }
}
