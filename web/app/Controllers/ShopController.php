<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Order;
use App\Resources\ProductImageResource;
use App\Services\Orders\OrderNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Store\Core\StoreAuth;
use Store\Core\View;
use Store\Services\ProductPage;
use Store\Services\ProductSearch;
use Store\Services\StoreCart;
use Store\Services\StoreFavorites;

class ShopController extends Controller
{
    public function __construct(
        private readonly OrderNotificationService $orderNotifications = new OrderNotificationService()
    ) {
    }

    public function catalog(?string $slug = null): never
    {
        $query = trim((string) (\App\Core\Request::query('q') ?? ''));
        $page = max(1, (int) (\App\Core\Request::query('page') ?? 1));
        $perPage = 12;
        $minPrice = is_numeric(\App\Core\Request::query('min_price')) ? max(0, (float) \App\Core\Request::query('min_price')) : null;
        $maxPrice = is_numeric(\App\Core\Request::query('max_price')) ? max(0, (float) \App\Core\Request::query('max_price')) : null;
        $inStock = \App\Core\Request::query('availability') === 'in_stock';
        $onSale = \App\Core\Request::query('sale') === '1';
        $sort = (string) (\App\Core\Request::query('sort') ?? 'featured');
        $sort = in_array($sort, ['featured', 'newest', 'price_asc', 'price_desc', 'name'], true) ? $sort : 'featured';
        $rawOptions = \App\Core\Request::query('option', []);
        $selectedOptions = [];

        if (is_array($rawOptions)) {
            foreach ($rawOptions as $optionSlug => $values) {
                if (!is_string($optionSlug) || preg_match('/^[a-z0-9-]+$/', $optionSlug) !== 1) {
                    continue;
                }

                $values = is_array($values) ? $values : [$values];
                $clean = array_values(array_unique(array_filter($values, static fn ($value): bool => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value) === 1)));

                if ($clean !== []) {
                    $selectedOptions[$optionSlug] = $clean;
                }
            }
        }
        $category = null;

        if ($slug !== null && $slug !== '') {
            $category = Category::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->with(['children' => static fn ($builder) => $builder->where('is_active', true)])
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

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with('frontImage');

        if ($category !== null) {
            $categoryIds = [(int) $category->id, ...$category->children->pluck('id')->map(static fn ($id): int => (int) $id)->all()];
            $productsQuery->whereIn('category_id', $categoryIds);
        }

        if ($query !== '') {
            $like = '%' . addcslashes(mb_substr($query, 0, 80), '%_\\') . '%';
            $productsQuery->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhereHas('variants', static fn (Builder $variants) => $variants->where('sku', 'like', $like));
            });
        }

        $filterBase = clone $productsQuery;
        $priceBounds = [
            'min' => (float) ((clone $filterBase)->min('price') ?? 0),
            'max' => (float) ((clone $filterBase)->max('price') ?? 0),
        ];
        $baseProductIds = (clone $filterBase)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $optionGroups = [];

        if ($baseProductIds !== []) {
            $availableOptions = ProductOption::query()
                ->whereIn('product_id', $baseProductIds)
                ->with('values')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($availableOptions as $option) {
                $slug = (string) $option->slug;
                $optionGroups[$slug] ??= ['slug' => $slug, 'name' => (string) $option->name, 'values' => []];

                foreach ($option->values as $value) {
                    $valueSlug = (string) $value->slug;
                    $optionGroups[$slug]['values'][$valueSlug] = [
                        'slug' => $valueSlug,
                        'name' => (string) $value->name,
                        'hex' => is_string($value->hex_color) ? $value->hex_color : null,
                    ];
                }
            }
        }

        foreach ($optionGroups as &$group) {
            $group['values'] = array_values($group['values']);
        }
        unset($group);
        $optionGroups = array_values($optionGroups);

        if ($minPrice !== null) {
            $productsQuery->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $productsQuery->where('price', '<=', $maxPrice);
        }

        if ($inStock) {
            $productsQuery->whereHas('variants', static fn (Builder $variants) => $variants->where('is_active', true)->where('stock', '>', 0));
        }

        if ($onSale) {
            $productsQuery->where(static function (Builder $builder): void {
                $builder
                    ->whereColumn('compare_at_price', '>', 'price')
                    ->orWhereHas('variants', static fn (Builder $variants) => $variants->where('is_active', true)->whereColumn('compare_at_price', '>', 'price'));
            });
        }

        foreach ($selectedOptions as $optionSlug => $values) {
            $productsQuery->whereHas('variants', static function (Builder $variants) use ($optionSlug, $values): void {
                $variants
                    ->where('is_active', true)
                    ->whereHas('variantValues', static function (Builder $rows) use ($optionSlug, $values): void {
                        $rows
                            ->whereHas('option', static fn (Builder $option) => $option->where('slug', $optionSlug))
                            ->whereHas('optionValue', static fn (Builder $value) => $value->whereIn('slug', $values));
                    });
            });
        }

        $total = (clone $productsQuery)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $productsQuery = match ($sort) {
            'newest' => $productsQuery->orderByDesc('created_at')->orderByDesc('id'),
            'price_asc' => $productsQuery->orderBy('price')->orderBy('id'),
            'price_desc' => $productsQuery->orderByDesc('price')->orderByDesc('id'),
            'name' => $productsQuery->orderBy('name')->orderBy('id'),
            default => $productsQuery->orderBy('sort_order')->orderByDesc('created_at')->orderByDesc('id'),
        };
        $products = $productsQuery
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $this->view('catalog', [
            'title' => $title,
            'query' => $query,
            'category' => $category,
            'products' => $products->map(static fn (Product $product): array => StoreFavorites::productCard($product))->all(),
            'favoriteIds' => StoreFavorites::ids(),
            'cartProductIds' => array_values(array_unique(array_map(static fn (array $line): int => (int) $line['product_id'], StoreCart::lines()))),
            'page' => $page,
            'lastPage' => $lastPage,
            'total' => $total,
            'filters' => [
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'inStock' => $inStock,
                'onSale' => $onSale,
                'sort' => $sort,
                'options' => $selectedOptions,
                'priceBounds' => $priceBounds,
                'optionGroups' => $optionGroups,
            ],
            'metaDescription' => $query !== ''
                ? 'Резултати от търсенето за „' . $query . '“ в онлайн магазин Borz33.'
                : ($category !== null
                    ? 'Разгледайте продуктите в категория „' . $category->name . '“ в онлайн магазин Borz33.'
                    : 'Разгледайте каталога и най-новите предложения в онлайн магазин Borz33.'),
        ]);
    }

    public function product(string $slug): never
    {
        $product = ProductPage::findActive($slug);

        if ($product === null) {
            View::renderError('Продуктът не е намерен.', 404);
        }

        $flash = StoreAuth::pullFlash();
        $frontImage = $product->frontImage;
        $metaDescription = trim((string) ($product->short_description ?: $product->description));

        $this->view('product', [
            'title' => $product->name . ' · Borz33',
            'product' => $product,
            'crumbs' => ProductPage::crumbs($product),
            'config' => ProductPage::config($product),
            'related' => ProductPage::related($product),
            'favoriteIds' => StoreFavorites::ids(),
            'message' => $flash['message'] ?? null,
            'isError' => (bool) ($flash['error'] ?? false),
            'metaDescription' => $metaDescription !== ''
                ? mb_substr($metaDescription, 0, 300)
                : 'Разгледайте ' . $product->name . ' и наличните варианти в онлайн магазин Borz33.',
            'metaImage' => $frontImage !== null ? ProductImageResource::toArray($frontImage)['url'] : null,
            'metaImageAlt' => $frontImage?->alt ?: $product->name,
            'ogType' => 'product',
            'productPrice' => (string) $product->price,
            'productCurrency' => 'EUR',
            'productAvailability' => 'in stock',
        ]);
    }

    public function quickView(string $slug): never
    {
        $product = ProductPage::findActive($slug);

        if ($product === null) {
            $this->json(['message' => 'Продуктът не е намерен.'], 404);
        }

        $config = ProductPage::config($product);
        $this->json(['data' => [
            'name' => (string) $product->name,
            'href' => '/products/' . $product->slug,
            'cartUrl' => '/products/' . $product->slug . '/cart',
            'price' => ProductPage::money($product->price),
            'image' => $config['gallery'][0]['url'] ?? null,
            'imageAlt' => $config['gallery'][0]['alt'] ?? $product->name,
            'config' => $config,
        ]]);
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
            if ($this->wantsJson()) {
                $this->json(['message' => 'Изберете наличен вариант.'], 422);
            }

            StoreAuth::setFlash('Изберете наличен вариант.', true);
            $this->redirect('/products/' . $product->slug);
        }

        $variantId = (int) $match['id'];

        $stock = (int) $match['stock'];

        if ($stock < 1) {
            if ($this->wantsJson()) {
                $this->json(['message' => 'Продуктът е изчерпан.'], 422);
            }

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
                if ($this->wantsJson()) {
                    $this->json(['message' => 'Полето ' . $field['name'] . ' е задължително.'], 422);
                }

                StoreAuth::setFlash('Полето ' . $field['name'] . ' е задължително.', true);
                $this->redirect('/products/' . $product->slug);
            }

            if ($field['max'] !== null && mb_strlen($text) > (int) $field['max']) {
                if ($this->wantsJson()) {
                    $this->json(['message' => 'Полето ' . $field['name'] . ' трябва да бъде най-много ' . $field['max'] . ' символа.'], 422);
                }

                StoreAuth::setFlash('Полето ' . $field['name'] . ' трябва да бъде най-много ' . $field['max'] . ' символа.', true);
                $this->redirect('/products/' . $product->slug);
            }

            if ($text !== '') {
                $personalization[$field['key']] = $text;
            }
        }

        StoreCart::add((int) $product->id, $variantId, $qty, $personalization);

        if ($this->wantsJson()) {
            $this->json([
                'message' => 'Добавено в количката.',
                'data' => $this->cartPayload(),
            ]);
        }

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

    public function checkout(): never
    {
        $lines = StoreCart::lines();

        if ($lines === []) {
            $this->redirect('/cart');
        }

        $user = \App\Core\Auth::user();
        $address = $user?->billingAddresses()->first();

        $this->view('checkout', [
            'title' => 'Детайли за поръчката · Borz33',
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
            'form' => [
                'first_name' => (string) ($address?->first_name ?: $user?->first_name),
                'last_name' => (string) ($address?->last_name ?: $user?->last_name),
                'email' => (string) ($user?->email ?? ''),
                'phone' => (string) ($user?->phone ?? ''),
                'delivery_method' => 'address',
                'address_line' => (string) ($address?->line1 ?? ''),
                'city' => (string) ($address?->city ?? ''),
                'postal_code' => (string) ($address?->postal_code ?? ''),
                'country' => (string) ($address?->country ?? 'България'),
                'payment_method' => 'cash_on_delivery',
                'notes' => '',
            ],
            'errors' => [],
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function placeOrder(): never
    {
        $this->assertCsrf();
        $lines = StoreCart::lines();

        if ($lines === []) {
            $this->redirect('/cart');
        }

        $keys = ['first_name', 'last_name', 'email', 'phone', 'delivery_method', 'address_line', 'city', 'postal_code', 'country', 'payment_method', 'notes'];
        $form = [];

        foreach ($keys as $key) {
            $form[$key] = trim((string) (\App\Core\Request::input($key) ?? ''));
        }

        $errors = [];

        foreach ([
            'first_name' => 'Въведете име.',
            'last_name' => 'Въведете фамилия.',
            'phone' => 'Въведете телефон.',
            'address_line' => 'Въведете адрес или офис.',
            'city' => 'Въведете населено място.',
            'country' => 'Въведете държава.',
        ] as $key => $message) {
            if ($form[$key] === '') {
                $errors[$key] = $message;
            }
        }

        foreach ([
            'first_name' => 100,
            'last_name' => 100,
            'email' => 191,
            'phone' => 40,
            'address_line' => 191,
            'city' => 100,
            'postal_code' => 16,
            'country' => 80,
        ] as $key => $limit) {
            if (mb_strlen($form[$key]) > $limit) {
                $errors[$key] = 'Полето може да бъде до ' . $limit . ' символа.';
            }
        }

        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Въведете валиден имейл.';
        }

        $phoneDigits = preg_replace('/\D+/', '', $form['phone']) ?? '';

        if ($form['phone'] !== '' && (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15)) {
            $errors['phone'] = 'Въведете валиден телефонен номер.';
        }

        if (!in_array($form['delivery_method'], ['address', 'office'], true)) {
            $errors['delivery_method'] = 'Изберете начин на доставка.';
        }

        if (!in_array($form['payment_method'], ['cash_on_delivery', 'bank_transfer'], true)) {
            $errors['payment_method'] = 'Изберете начин на плащане.';
        }

        if (mb_strlen($form['notes']) > 1000) {
            $errors['notes'] = 'Бележката може да бъде до 1000 символа.';
        }

        if ($errors !== []) {
            $this->view('checkout', [
                'title' => 'Детайли за поръчката · Borz33',
                'lines' => $lines,
                'total' => StoreCart::moneyTotal($lines),
                'form' => $form,
                'errors' => $errors,
                'status' => 422,
                'robots' => 'noindex, nofollow',
            ]);
        }

        $sum = array_reduce($lines, static fn (float $carry, array $line): float => $carry + (float) $line['total'], 0.0);
        $user = \App\Core\Auth::user();

        $order = Capsule::connection()->transaction(static function () use ($form, $lines, $sum, $user): Order {
            $order = Order::query()->create([
                ...$form,
                'user_id' => $user?->id,
                'number' => 'BZ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'status' => 'pending',
                'currency' => 'EUR',
                'subtotal' => $sum,
                'total' => $sum,
                'postal_code' => $form['postal_code'] !== '' ? $form['postal_code'] : null,
                'notes' => $form['notes'] !== '' ? $form['notes'] : null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'] > 0 ? $line['variant_id'] : null,
                    'name' => $line['name'],
                    'sku' => $line['sku'] ?: null,
                    'options' => $line['options'] ?: null,
                    'notes' => $line['notes'] !== [] ? implode("\n", $line['notes']) : null,
                    'qty' => $line['qty'],
                    'unit_price' => $line['price'],
                    'total' => $line['total'],
                ]);
            }

            return $order;
        });

        $order->load('items');
        $mailStatus = $this->orderNotifications->send($order);
        StoreCart::clear();
        $_SESSION['store_last_order_id'] = (int) $order->id;
        $_SESSION['store_last_order_customer_email_sent'] = $mailStatus['customer'];
        $this->redirect('/checkout/success?order=' . rawurlencode((string) $order->number));
    }

    public function checkoutSuccess(): never
    {
        $number = trim((string) (\App\Core\Request::query('order') ?? ''));
        $order = Order::query()->with('items')->where('number', $number)->find((int) ($_SESSION['store_last_order_id'] ?? 0));
        if ($order === null) $this->redirect('/catalog');
        $this->view('checkout-success', [
            'title' => 'Поръчката е приета · Borz33',
            'order' => $order,
            'customerEmailSent' => (bool) ($_SESSION['store_last_order_customer_email_sent'] ?? false),
            'robots' => 'noindex, nofollow',
        ]);
    }

    public function cartData(): never
    {
        $this->json(['data' => $this->cartPayload()]);
    }

    public function updateCart(string $index): never
    {
        $this->assertCsrf();
        $line = (int) $index;
        $qty = (int) (\App\Core\Request::input('qty') ?? 0);

        StoreCart::updateQty($line, $qty);

        if ($this->wantsJson()) {
            $this->json(['data' => $this->cartPayload()]);
        }

        $this->redirect('/cart');
    }

    public function removeCart(string $index): never
    {
        $this->assertCsrf();
        StoreCart::remove((int) $index);

        if ($this->wantsJson()) {
            $this->json(['data' => $this->cartPayload()]);
        }

        $this->redirect('/cart');
    }

    /** @return array{count: int, lines: list<array<string, mixed>>, total: string} */
    private function cartPayload(): array
    {
        $lines = StoreCart::lines();

        return [
            'count' => StoreCart::count(),
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
        ];
    }

    public function favorites(): never
    {
        $products = StoreFavorites::products();

        $this->view('favorites', [
            'title' => 'Любими · Borz33',
            'products' => $products->map(static fn ($product): array => StoreFavorites::productCard($product))->all(),
        ]);
    }

    public function toggleFavorite(string $id): never
    {
        $this->assertCsrf();

        try {
            $favorite = StoreFavorites::toggle((int) $id);
        } catch (\InvalidArgumentException $error) {
            $this->json(['message' => $error->getMessage()], 404);
        }

        $this->json([
            'message' => $favorite ? 'Добавено в любими.' : 'Премахнато от любими.',
            'data' => [
                'product_id' => (int) $id,
                'favorite' => $favorite,
                'count' => StoreFavorites::count(),
            ],
        ]);
    }
}
