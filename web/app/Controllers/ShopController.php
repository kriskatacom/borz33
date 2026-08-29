<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Category;
use Store\Core\View;

class ShopController extends Controller
{
    public function catalog(?string $slug = null): never
    {
        $query = trim((string) (\App\Core\Request::query('q') ?? ''));
        $category = null;

        if ($slug !== null && $slug !== '') {
            $category = Category::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($category === null) {
                View::renderError('Категорията не е намерена.', 404);
            }
        }

        if ($query !== '') {
            $title = 'Търсене · Borz33';
        } elseif ($category !== null) {
            $title = $category->name . ' · Borz33';
        } else {
            $title = 'Каталог · Borz33';
        }

        $this->view('catalog', [
            'title' => $title,
            'query' => $query,
            'category' => $category,
        ]);
    }

    public function cart(): never
    {
        $this->view('cart', [
            'title' => 'Количка · Borz33',
        ]);
    }

    public function favorites(): never
    {
        $this->view('favorites', [
            'title' => 'Любими · Borz33',
        ]);
    }
}
