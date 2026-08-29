<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class DashboardAdminService
{
    public const LOW_STOCK = 5;

    /** @return array<string, int> */
    public function summary(): array
    {
        return [
            'products_active' => Product::query()->where('is_active', true)->count(),
            'low_stock' => ProductVariant::query()
                ->where('is_active', true)
                ->where('stock', '<=', self::LOW_STOCK)
                ->whereHas('product', static function ($query): void {
                    $query->where('is_active', true);
                })
                ->count(),
            'banners_active' => Banner::query()->where('is_active', true)->count(),
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'categories_active' => Category::query()->where('is_active', true)->count(),
            'pages_active' => Page::query()->where('is_active', true)->count(),
            'media' => MediaFile::query()->count(),
        ];
    }
}
