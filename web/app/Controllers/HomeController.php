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

        $bestSellers = Product::query()
            ->select('products.*')
            ->selectRaw('SUM(order_items.qty) as sold_quantity')
            ->join('order_items', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.is_active', true)
            ->where('orders.status', 'delivered')
            ->with('frontImage')
            ->groupBy('products.id')
            ->orderByDesc('sold_quantity')
            ->orderByDesc('products.id')
            ->limit(8)
            ->get();

        $this->view('home', [
            'title' => 'Borz33 · Онлайн магазин',
            'metaDescription' => 'Открийте най-новите продукти и внимателно подбрани предложения в онлайн магазин Borz33.',
            'newProducts' => $products->map(static fn (Product $product): array => StoreFavorites::productCard($product))->all(),
            'bestSellers' => $bestSellers->map(static fn (Product $product): array => StoreFavorites::productCard($product) + ['soldQuantity' => (int) $product->sold_quantity])->all(),
            'favoriteIds' => StoreFavorites::ids(),
        ]);
    }
}
