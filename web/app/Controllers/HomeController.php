<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Product;
use Store\Services\StoreFavorites;

class HomeController extends Controller
{
    public function index(): never
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('frontImage')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $this->view('home', [
            'title' => 'Borz33 · Онлайн магазин',
            'metaDescription' => 'Открийте най-новите продукти и внимателно подбрани предложения в онлайн магазин Borz33.',
            'newProducts' => $products->map(static fn (Product $product): array => StoreFavorites::productCard($product))->all(),
            'favoriteIds' => StoreFavorites::ids(),
        ]);
    }
}
