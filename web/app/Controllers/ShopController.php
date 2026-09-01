<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductOption;
use App\Models\Order;
use App\Resources\ProductImageResource;
use App\Services\Orders\OrderNotificationService;
use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\InvoiceNotificationService;
use App\Services\Users\BillingAddressService;
use App\Services\Notifications\AdminNotificationService;
use App\Services\Shipping\EcontShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Store\Core\StoreAuth;
use Store\Core\View;
use Store\Services\ProductPage;
use Store\Services\ProductSearch;
use Store\Services\RecentlyViewedProducts;
use Store\Services\StoreCart;
use Store\Services\StoreFavorites;

class ShopController extends Controller
{
    public function __construct(
        private readonly OrderNotificationService $orderNotifications = new OrderNotificationService(),
        private ?EcontShippingService $econtShipping = null,
        private readonly InvoiceService $invoices = new InvoiceService(),
        private readonly InvoiceNotificationService $invoiceNotifications = new InvoiceNotificationService(),
        private readonly BillingAddressService $addresses = new BillingAddressService(),
        private readonly AdminNotificationService $adminNotifications = new AdminNotificationService()
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

        RecentlyViewedProducts::record((int) $product->id);

        $flash = StoreAuth::pullFlash();
        $frontImage = $product->frontImage;
        $metaDescription = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($product->short_description ?: $product->description)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

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

        RecentlyViewedProducts::record((int) $product->id);

        $config = ProductPage::config($product);
        $this->json(['data' => [
            'name' => (string) $product->name,
            'href' => '/products/' . $product->slug,
            'cartUrl' => '/products/' . $product->slug . '/cart',
            'price' => ProductPage::money($product->price),
            'weight' => ProductPage::weight($product->weight_grams),
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
        $recentlyViewed = RecentlyViewedProducts::products();

        $this->view('cart', [
            'title' => 'Количка · Borz33',
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
            'totalWeight' => StoreCart::weightTotal($lines),
            'message' => $flash['message'] ?? null,
            'isError' => (bool) ($flash['error'] ?? false),
            'recentlyViewed' => $recentlyViewed->map(static fn (Product $product): array => StoreFavorites::productCard($product))->all(),
            'favoriteIds' => StoreFavorites::ids(),
            'cartProductIds' => array_values(array_unique(array_map(static fn (array $line): int => (int) $line['product_id'], $lines))),
        ]);
    }

    public function checkout(): never
    {
        $lines = StoreCart::lines();

        if ($lines === []) {
            $this->redirect('/cart');
        }

        $user = \App\Core\Auth::user();
        $addresses = $user?->billingAddresses()->get() ?? new \Illuminate\Support\Collection();
        $personalAddress = $addresses->first(static fn (\App\Models\UserAddress $address): bool => $address->party === \App\Models\UserAddress::PARTY_PERSON);
        $companyAddress = $addresses->first(static fn (\App\Models\UserAddress $address): bool => $address->party === \App\Models\UserAddress::PARTY_COMPANY);
        $deliveryAddress = $personalAddress ?? $companyAddress;
        $subtotal = $this->cartSubtotal($lines);
        $freeShippingThreshold = $this->freeShippingThreshold();

        $this->view('checkout', [
            'title' => 'Детайли за поръчката · Borz33',
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
            'totalWeight' => StoreCart::weightTotal($lines),
            'subtotalAmount' => $subtotal,
            'freeShippingThreshold' => $freeShippingThreshold,
            'form' => [
                'first_name' => (string) ($personalAddress?->first_name ?: $user?->first_name),
                'last_name' => (string) ($personalAddress?->last_name ?: $user?->last_name),
                'email' => (string) ($user?->email ?? ''),
                'phone' => (string) ($user?->phone ?? ''),
                'delivery_method' => 'address',
                'shipping_payer' => 'receiver',
                'econt_office_code' => '',
                'address_line' => (string) ($deliveryAddress?->line1 ?? ''),
                'city' => (string) ($deliveryAddress?->city ?? ''),
                'postal_code' => (string) ($deliveryAddress?->postal_code ?? ''),
                'country' => 'България',
                'payment_method' => 'cash_on_delivery',
                'notes' => '',
                'invoice_company' => (string) ($companyAddress?->company_name ?? ''),
                'invoice_eik' => (string) ($companyAddress?->eik ?? ''),
                'invoice_vat_number' => (string) ($companyAddress?->vat_number ?? ''),
                'invoice_address' => (string) ($companyAddress?->line1 ?? ''),
                'invoice_mol' => (string) ($companyAddress?->mol ?? ''),
            ],
            'errors' => [],
            'acceptedTerms' => false,
            'wantsInvoice' => false,
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

        $keys = ['first_name', 'last_name', 'email', 'phone', 'delivery_method', 'shipping_payer', 'econt_office_code', 'address_line', 'city', 'postal_code', 'country', 'payment_method', 'notes', 'invoice_company', 'invoice_eik', 'invoice_vat_number', 'invoice_address', 'invoice_mol'];
        $form = [];

        foreach ($keys as $key) {
            $form[$key] = trim((string) (\App\Core\Request::input($key) ?? ''));
        }

        $errors = [];
        $wantsInvoice = \App\Core\Request::wantsTrue('invoice_requested');
        $sum = $this->cartSubtotal($lines);
        $freeShippingThreshold = $this->freeShippingThreshold();

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
            'econt_office_code' => 20,
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

        if ($form['delivery_method'] === 'address' && $form['postal_code'] === '') {
            $errors['postal_code'] = 'Въведете пощенски код за изчисляване на доставката.';
        }

        if ($form['delivery_method'] === 'office' && $form['econt_office_code'] === '') {
            $errors['address_line'] = 'Изберете офис от картата на Еконт.';
        } elseif ($form['delivery_method'] === 'office' && preg_match('/^\d{1,20}$/', $form['econt_office_code']) !== 1) {
            $errors['address_line'] = 'Избраната Econt локация е невалидна.';
        }
        if (!in_array($form['shipping_payer'], ['receiver', 'sender'], true)) {
            $errors['shipping_payer'] = 'Изберете кой плаща доставката.';
        } elseif ($form['shipping_payer'] === 'sender' && $sum <= $freeShippingThreshold) {
            $errors['shipping_payer'] = 'Безплатната доставка е достъпна само за поръчки над ' . ProductPage::money($freeShippingThreshold) . '.';
        }

        if ($form['payment_method'] !== 'cash_on_delivery') {
            $errors['payment_method'] = 'Поддържаният начин на плащане е наложен платеж.';
        }

        if (mb_strtolower($form['country']) !== 'българия') {
            $errors['country'] = 'Калкулаторът на Еконт поддържа доставки в България.';
        }

        if (mb_strlen($form['notes']) > 1000) {
            $errors['notes'] = 'Бележката може да бъде до 1000 символа.';
        }

        if ($wantsInvoice) {
            foreach (['invoice_company' => 'Въведете име на фирмата.', 'invoice_eik' => 'Въведете ЕИК.', 'invoice_address' => 'Въведете адрес за фактура.', 'invoice_mol' => 'Въведете МОЛ.'] as $key => $message) if ($form[$key] === '') $errors[$key] = $message;
            if ($form['invoice_eik'] !== '' && preg_match('/^\d{9,13}$/', $form['invoice_eik']) !== 1) $errors['invoice_eik'] = 'ЕИК трябва да съдържа между 9 и 13 цифри.';
            if ($form['invoice_vat_number'] !== '' && preg_match('/^BG\d{9,10}$/i', $form['invoice_vat_number']) !== 1) $errors['invoice_vat_number'] = 'ДДС номерът трябва да е във формат BG и 9 или 10 цифри.';
            foreach (['invoice_company' => 191, 'invoice_eik' => 16, 'invoice_vat_number' => 20, 'invoice_address' => 255, 'invoice_mol' => 191] as $key => $limit) if (mb_strlen($form[$key]) > $limit) $errors[$key] = 'Полето може да бъде до ' . $limit . ' символа.';
        }

        $acceptedTerms = \App\Core\Request::wantsTrue('accept_terms');

        if (!$acceptedTerms) {
            $errors['accept_terms'] = 'Потвърдете условията, за да завършите поръчката.';
        }

        foreach ($lines as $line) {
            if ((int) $line['variant_id'] < 1) {
                continue;
            }

            $variant = ProductVariant::query()->find((int) $line['variant_id']);
            if ($variant === null || !$variant->isActive() || (int) $variant->stock < (int) $line['qty']) {
                $errors['stock'] = 'Наличността на един или повече избрани варианти вече не е достатъчна. Обновете количката и опитайте отново.';
                break;
            }
        }

        $shipping = null;

        if ($errors === []) {
            try { $shipping = $this->econtShipping()->quote($this->shippingQuoteInput($form, $lines, $sum)); }
            catch (\Throwable $exception) { $errors['shipping'] = $exception->getMessage(); }
            if ($shipping !== null && $shipping['currency'] !== 'EUR') $errors['shipping'] = 'Цената за доставка трябва да бъде в EUR.';
        }

        if ($errors !== []) {
            $this->view('checkout', [
                'title' => 'Детайли за поръчката · Borz33',
                'lines' => $lines,
                'total' => StoreCart::moneyTotal($lines),
                'totalWeight' => StoreCart::weightTotal($lines),
                'subtotalAmount' => $sum,
                'freeShippingThreshold' => $freeShippingThreshold,
                'form' => $form,
                'errors' => $errors,
                'acceptedTerms' => $acceptedTerms,
                'wantsInvoice' => $wantsInvoice,
                'status' => 422,
                'robots' => 'noindex, nofollow',
            ]);
        }

        $shippingAmount = (float) $shipping['amount'];
        $user = \App\Core\Auth::user();

        $vatSettings = \App\Models\SiteSetting::query()->firstOrCreate([]);
        $company = require dirname(__DIR__, 3) . '/config/company.php';
        $vatEnabled = (bool) $vatSettings->vat_enabled;
        $vatRate = $vatEnabled ? max(0, (float) $company['vat_rate']) : 0.0;

        $order = Capsule::connection()->transaction(function () use ($form, $lines, $sum, $shippingAmount, $shipping, $user, $wantsInvoice, $vatEnabled, $vatRate): Order {
            $order = Order::query()->create([
                ...$form,
                'user_id' => $user?->id,
                'number' => 'BZ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'status' => 'pending',
                'currency' => 'EUR',
                'vat_enabled' => $vatEnabled,
                'vat_rate' => $vatRate,
                'subtotal' => $sum,
                'shipping_amount' => $shippingAmount,
                'total' => $sum + $shippingAmount,
                'econt_office_code' => $form['delivery_method'] === 'office' ? $form['econt_office_code'] : null,
                'econt_quote_snapshot' => $shipping,
                'postal_code' => $form['postal_code'] !== '' ? $form['postal_code'] : null,
                'notes' => $form['notes'] !== '' ? $form['notes'] : null,
                'invoice_requested' => $wantsInvoice,
                'invoice_company' => $wantsInvoice ? $form['invoice_company'] : null,
                'invoice_eik' => $wantsInvoice ? $form['invoice_eik'] : null,
                'invoice_vat_number' => $wantsInvoice && $form['invoice_vat_number'] !== '' ? strtoupper($form['invoice_vat_number']) : null,
                'invoice_address' => $wantsInvoice ? $form['invoice_address'] : null,
                'invoice_mol' => $wantsInvoice ? $form['invoice_mol'] : null,
            ]);

            // The checkout already reads the saved profile phone on the next visit.
            // Save it only when the customer profile has no phone yet; a later order
            // must never silently replace a number deliberately stored by the user.
            if ($user !== null && trim((string) ($user->phone ?? '')) === '') {
                \App\Models\User::query()
                    ->whereKey($user->id)
                    ->where(static fn ($query) => $query->whereNull('phone')->orWhere('phone', ''))
                    ->update(['phone' => $form['phone'], 'updated_at' => date('Y-m-d H:i:s')]);
            }

            if ($user !== null) {
                $this->addresses->rememberOrderAddresses($user, $form, $wantsInvoice);
            }

            foreach ($lines as $line) {
                if ((int) $line['variant_id'] > 0) {
                    $updated = ProductVariant::query()
                        ->whereKey((int) $line['variant_id'])
                        ->where('is_active', true)
                        ->where('stock', '>=', (int) $line['qty'])
                        ->decrement('stock', (int) $line['qty']);

                    if ($updated !== 1) {
                        throw new \RuntimeException('Наличността на един или повече избрани варианти вече не е достатъчна. Обновете количката и опитайте отново.');
                    }
                    $purchasedVariant = ProductVariant::query()->with(['product.frontImage', 'image'])->find((int) $line['variant_id']);
                    if ($purchasedVariant !== null && $purchasedVariant->product !== null) {
                        $this->adminNotifications->stockDepletedAfterPurchase($purchasedVariant->product, $purchasedVariant, (int) $line['qty'], (string) $order->number);
                    }
                }

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
        // Every order has an immutable invoice PDF in the archive. The checkbox controls
        // delivery to the customer, not whether the accounting document is created.
        $invoice = $this->invoices->createForOrder($order, true);
        if ($wantsInvoice) $this->invoiceNotifications->send($invoice);
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

    public function shippingQuote(): never
    {
        $this->assertCsrf();
        $lines = StoreCart::lines();

        if ($lines === []) {
            $this->json(['message' => 'Количката е празна.'], 422);
        }

        $deliveryMethod = trim((string) (\App\Core\Request::input('delivery_method') ?? ''));

        $subtotal = array_reduce($lines, static fn (float $carry, array $line): float => $carry + (float) $line['total'], 0.0);

        if (!in_array($deliveryMethod, ['address', 'office'], true)) {
            $this->json(['message' => 'Изберете начин на доставка.'], 422);
        }
        $form = [];
        foreach (['first_name','last_name','phone','delivery_method','shipping_payer','city','postal_code','address_line','econt_office_code','payment_method'] as $key) $form[$key] = trim((string) (\App\Core\Request::input($key) ?? ''));
        if ($form['first_name'] === '' || $form['last_name'] === '' || $form['phone'] === '' || $form['city'] === '' || $form['postal_code'] === '') $this->json(['message' => 'Попълнете име, телефон, населено място и пощенски код.'], 422);
        if ($deliveryMethod === 'office' && $form['econt_office_code'] === '') $this->json(['message' => 'Изберете офис на Econt от картата.'], 422);
        if ($form['payment_method'] !== 'cash_on_delivery') $this->json(['message' => 'Поддържаният начин на плащане е наложен платеж.'], 422);
        if (!in_array($form['shipping_payer'], ['receiver', 'sender'], true)) $this->json(['message' => 'Изберете кой плаща доставката.'], 422);
        $freeShippingThreshold = $this->freeShippingThreshold();
        if ($form['shipping_payer'] === 'sender' && $subtotal <= $freeShippingThreshold) $this->json(['message' => 'Безплатната доставка е достъпна само за поръчки над ' . ProductPage::money($freeShippingThreshold) . '.'], 422);
        try { $quote = $this->econtShipping()->quote($this->shippingQuoteInput($form, $lines, $subtotal)); }
        catch (\Throwable $exception) { $this->json(['message' => $exception->getMessage()], 422); }

        if ($quote['currency'] !== 'EUR') {
            $this->json(['message' => 'Цената за доставка трябва да бъде в EUR.'], 500);
        }

        $this->json(['data' => [
            'amount' => $quote['amount'],
            'currency' => $quote['currency'],
            'formatted' => ProductPage::money($quote['amount']),
            'carrier_formatted' => ProductPage::money($quote['carrier_amount']),
            'grand_total_formatted' => ProductPage::money($subtotal + $quote['amount']),
            'environment' => $quote['environment'],
            'expected_delivery_date' => $quote['expected_delivery_date'],
        ]]);
    }

    /** @param array<string,string> $form @param list<array<string,mixed>> $lines @return array<string,mixed> */
    private function shippingQuoteInput(array $form, array $lines, float $subtotal): array
    {
        $grams = array_sum(array_map(static fn (array $line): int => (int) ($line['total_weight_grams'] ?? 0), $lines));
        if ($grams < 1) throw new \RuntimeException('Липсва тегло на продукт. Доставката не може да бъде изчислена.');
        return ['delivery_method' => (string) $form['delivery_method'], 'shipping_payer' => (string) ($form['shipping_payer'] ?? 'receiver'), 'first_name' => (string) $form['first_name'], 'last_name' => (string) $form['last_name'], 'phone' => (string) $form['phone'], 'city' => (string) $form['city'], 'postal_code' => (string) $form['postal_code'], 'address_line' => (string) $form['address_line'], 'econt_office_code' => (string) ($form['econt_office_code'] ?? ''), 'weight_kg' => $grams / 1000, 'order_value' => $subtotal, 'cod_amount' => $subtotal];
    }

    /** @param list<array<string,mixed>> $lines */
    private function cartSubtotal(array $lines): float
    {
        return round(array_reduce($lines, static fn (float $carry, array $line): float => $carry + (float) $line['total'], 0.0), 2);
    }

    private function freeShippingThreshold(): float
    {
        return max(0, (float) \App\Models\SiteSetting::query()->firstOrCreate([])->free_shipping_threshold);
    }

    private function econtShipping(): EcontShippingService
    {
        return $this->econtShipping ??= new EcontShippingService();
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
        $current = StoreCart::lines()[$line] ?? null;

        if (is_array($current) && $qty > (int) ($current['stock'] ?? StoreCart::MAX_QTY)) {
            $message = 'Няма достатъчна наличност. Максималното количество за този продукт е ' . (int) $current['stock'] . '.';

            if ($this->wantsJson()) {
                $this->json(['message' => $message, 'data' => $this->cartPayload()], 422);
            }

            StoreAuth::setFlash($message, true);
            $this->redirect('/cart');
        }

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

    /** @return array{count: int, lines: list<array<string, mixed>>, total: string, subtotal: float, totalWeight: string} */
    private function cartPayload(): array
    {
        $lines = StoreCart::lines();

        return [
            'count' => StoreCart::count(),
            'lines' => $lines,
            'total' => StoreCart::moneyTotal($lines),
            'subtotal' => $this->cartSubtotal($lines),
            'totalWeight' => StoreCart::weightTotal($lines),
        ];
    }

    public function favorites(): never
    {
        $products = StoreFavorites::products();

        $this->view('favorites', [
            'title' => 'Любими · Borz33',
            'products' => $products->map(static fn ($product): array => StoreFavorites::productCard($product))->all(),
            'cartProductIds' => array_values(array_unique(array_map(static fn (array $line): int => (int) $line['product_id'], StoreCart::lines()))),
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
