<?php

declare(strict_types=1);

namespace Store\Services;

use App\Core\Auth;
use App\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

final class RecentlyViewedProducts
{
    private const SESSION_KEY = 'store_recently_viewed_products';
    private const MAX_STORED = 20;

    public static function record(int $productId): void
    {
        if ($productId < 1) {
            return;
        }

        $ids = array_values(array_filter(self::sessionIds(), static fn (int $id): bool => $id !== $productId));
        array_unshift($ids, $productId);
        $_SESSION[self::SESSION_KEY] = array_slice($ids, 0, self::MAX_STORED);

        $user = Auth::user();
        if ($user === null) {
            return;
        }

        self::mergeGuestHistory();
        Capsule::table('user_recently_viewed_products')->updateOrInsert(
            ['user_id' => (int) $user->id, 'product_id' => $productId],
            ['viewed_at' => date('Y-m-d H:i:s')]
        );
        self::trimUserHistory((int) $user->id);
    }

    /** @return Collection<int, Product> */
    public static function products(int $limit = 8): Collection
    {
        $user = Auth::user();
        if ($user !== null) {
            self::mergeGuestHistory();
            $ids = Capsule::table('user_recently_viewed_products')
                ->where('user_id', (int) $user->id)
                ->orderByDesc('viewed_at')
                ->limit(max(1, $limit))
                ->pluck('product_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        } else {
            $ids = array_slice(self::sessionIds(), 0, max(1, $limit));
        }

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

    /** @return list<int> */
    private static function sessionIds(): array
    {
        $raw = $_SESSION[self::SESSION_KEY] ?? [];

        return is_array($raw)
            ? array_values(array_unique(array_filter(array_map('intval', $raw), static fn (int $id): bool => $id > 0)))
            : [];
    }

    private static function mergeGuestHistory(): void
    {
        $user = Auth::user();
        $ids = self::sessionIds();
        if ($user === null || $ids === []) {
            return;
        }

        $time = time();
        foreach (array_reverse($ids) as $index => $productId) {
            Capsule::table('user_recently_viewed_products')->updateOrInsert(
                ['user_id' => (int) $user->id, 'product_id' => $productId],
                ['viewed_at' => date('Y-m-d H:i:s', $time + $index)]
            );
        }

        unset($_SESSION[self::SESSION_KEY]);
        self::trimUserHistory((int) $user->id);
    }

    private static function trimUserHistory(int $userId): void
    {
        $keep = Capsule::table('user_recently_viewed_products')
            ->where('user_id', $userId)
            ->orderByDesc('viewed_at')
            ->limit(self::MAX_STORED)
            ->pluck('product_id')
            ->all();

        Capsule::table('user_recently_viewed_products')
            ->where('user_id', $userId)
            ->when($keep !== [], static fn ($query) => $query->whereNotIn('product_id', $keep))
            ->delete();
    }
}
