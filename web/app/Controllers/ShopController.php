<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Category;
use Store\Core\StoreAuth;
use Store\Core\View;
use Store\Services\ProductPage;
use Store\Services\ProductSearch;
use Store\Services\StoreCart;

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

    public function product(string $slug): never
    {
        $product = ProductPage::findActive($slug);

        if ($product === null) {
            View::renderError('Продуктът не е намерен.', 404);
        }

        $flash = StoreAuth::pullFlash();

        $this->view('product', [
            'title' => $product->name . ' · Borz33',
            'product' => $product,
            'crumbs' => ProductPage::crumbs($product),
            'config' => ProductPage::config($product),
            'related' => ProductPage::related($product),
            'message' => $flash['message'] ?? null,
            'isError' => (bool) ($flash['error'] ?? false),
        ]);
    }

    public function addToCart(string $slug): never
    {
        $this->assertCsrf();
        $product = ProductPage::findActive($slug);

        if ($product === null) {
            View::renderError('Продуктът не е намерен.', 404);
        }

        $config = ProductPage::config($product);
        $variantId = (int) (\App\Core\Request::input('variant_id') ?? 0);
        $qty = (int) (\App\Core\Request::input('qty') ?? 1);
        $postedOptions = \App\Core\Request::input('options');
        $match = ProductPage::postedVariant(
            $config,
            $variantId,
            is_array($postedOptions) ? $postedOptions : []
        );

        if ($match === null) {
            StoreAuth::setFlash('Изберете наличен вариант.', true);
            $this->redirect('/products/' . $product->slug);
        }

        $variantId = (int) $match['id'];

        $stock = (int) $match['stock'];

        if ($stock < 1) {
            StoreAuth::setFlash('Продуктът е изчерпан.', true);
            $this->redirect('/products/' . $product->slug);
        }

        $qty = max(1, min(StoreCart::MAX_QTY, min($stock, $qty)));
        $posted = \App\Core\Request::input('personalization');
        $posted = is_array($posted) ? $posted : [];
        $personalization = [];

        foreach ($config['fields'] as $field) {
            $raw = $posted[$field['key']] ?? '';
            $text = is_string($raw) ? trim($raw) : '';

            if ($field['required'] && $text === '') {
                StoreAuth::setFlash('Полето ' . $field['name'] . ' е задължително.', true);
                $this->redirect('/products/' . $product->slug);
            }

            if ($field['max'] !== null && mb_strlen($text) > (int) $field['max']) {
                StoreAuth::setFlash('Полето ' . $field['name'] . ' трябва да бъде най-много ' . $field['max'] . ' символа.', true);
                $this->redirect('/products/' . $product->slug);
            }

            if ($text !== '') {
                $personalization[$field['key']] = $text;
            }
        }

        StoreCart::add((int) $product->id, $variantId, $qty, $personalization);
        StoreAuth::setFlash('Добавено в количката.');
        $this->redirect('/products/' . $product->slug);
    }

    public function search(): never
    {
        $query = trim((string) (\App\Core\Request::query('q') ?? ''));

        $this->json([
            'data' => ProductSearch::suggest($query),
        ]);
    }

    public function cart(): never
    {
        $flash = StoreAuth::pullFlash();
        $lines = StoreCart::lines();

        $this->view('cart', [
            'title' => 'Количка · Borz33',
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
            'message' => $flash['message'] ?? null,
            'isError' => (bool) ($flash['error'] ?? false),
        ]);
    }

    public function updateCart(string $index): never
    {
        $this->assertCsrf();
        $line = (int) $index;
        $qty = (int) (\App\Core\Request::input('qty') ?? 0);

        StoreCart::updateQty($line, $qty);
        $this->redirect('/cart');
    }

    public function removeCart(string $index): never
    {
        $this->assertCsrf();
        StoreCart::remove((int) $index);
        $this->redirect('/cart');
    }

    public function favorites(): never
    {
        $this->view('favorites', [
            'title' => 'Любими · Borz33',
        ]);
    }
}
