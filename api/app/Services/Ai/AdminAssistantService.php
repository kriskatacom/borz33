<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Exceptions\AuthException;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

final class AdminAssistantService
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 4) . '/config/openai.php';
    }

    /** @return array{answer:string, links:list<array{label:string,to:string}>} */
    public function answer(string $message, string $currentPath = '/'): array
    {
        $message = trim($message);
        if ($message === '' || mb_strlen($message) > 2000) {
            throw new AuthException('Въведете въпрос до 2000 знака.', 422);
        }

        $key = trim((string) ($this->config['api_key'] ?? ''));
        if (($this->config['admin_assistant_enabled'] ?? true) !== true) {
            throw new AuthException('AI асистентът е изключен от конфигурацията.', 503);
        }
        if ($key === '') throw new AuthException('AI асистентът не е конфигуриран локално.', 503);

        $tools = $this->tools();
        $payload = [
            'model' => (string) $this->config['product_model'],
            'store' => false,
            'instructions' => 'Ти си помощник за администратора на български онлайн магазин. Отговаряй кратко и ясно само на български. Магазинът работи с валута евро (EUR). Винаги показвай паричните суми в евро и не ги преобразувай в друга валута. Имаш само read-only справочни инструменти. Никога не измисляй числа. Когато показваш продукти, варианти, поръчки или категории, използвай bullet списък с „•“, като всеки елемент е на отделен ред. Не използвай таблици за такива списъци. Използвай само българските имена на разделите, без английски преводи. Обяснявай от кой раздел може да се направи действие и добавяй навигационна препоръка в края. Не казвай, че си променил данни. При „днес“, „вчера“, „тази седмица“, „миналата седмица“, „този месец“, „миналия месец“ или друг относителен период винаги използвай date_context или orders_period_summary, вместо сам да изчисляваш датите. При сравнение на два периода извикай orders_period_summary по веднъж за всеки период и сравни върнатите бройки и суми. Използвай следната карта на администрацията за въпроси от типа „къде е“ и „как се прави“:\n\n' . $this->knowledge(),
            'input' => [['role' => 'user', 'content' => 'Текуща страница: ' . $currentPath . "\nВъпрос: " . $message]],
            'tools' => $tools,
            'tool_choice' => 'auto',
            'max_output_tokens' => 900,
        ];

        $response = $this->request($payload, $key);
        $calls = $this->functionCalls($response);
        $links = $this->links($message);
        if ($calls !== []) {
            $outputs = [];
            foreach (array_slice($calls, 0, 3) as $call) {
                $toolResult = $this->execute($call['name'], $call['arguments']);
                $links = $this->mergeLinks($links, is_array($toolResult['_links'] ?? null) ? $toolResult['_links'] : []);
                $outputs[] = ['type' => 'function_call_output', 'call_id' => $call['call_id'], 'output' => json_encode($toolResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
            }
            $response = $this->request(['model' => (string) $this->config['product_model'], 'store' => false, 'instructions' => $payload['instructions'], 'input' => array_merge($payload['input'], $response['output'] ?? [], $outputs), 'tools' => $tools, 'max_output_tokens' => 900], $key);
        }

        $answer = $this->text($response);
        return ['answer' => $answer !== '' ? $answer : 'Не успях да подготвя справка. Опитайте с по-конкретен въпрос.', 'links' => $links];
    }

    /** @return list<array<string, mixed>> */
    private function tools(): array
    {
        return array_map(static fn (array $tool): array => ['type' => 'function', 'name' => $tool['name'], 'description' => $tool['description'], 'strict' => true, 'parameters' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Текст, име, номер, SKU или имейл за търсене.']], 'required' => ['query'], 'additionalProperties' => false]], [
            ['name' => 'store_overview', 'description' => 'Брой активни продукти, категории, поръчки и фактури.'],
            ['name' => 'low_stock_products', 'description' => 'Продукти и варианти под минималната наличност от настройките.'],
            ['name' => 'recent_orders', 'description' => 'Последните поръчки с номер, клиент, статус и сума.'],
            ['name' => 'date_context', 'description' => 'Връща точните дати за днес, вчера, тази седмица, миналата седмица, този месец и миналия месец. Използвай за относителни периоди.'],
            ['name' => 'orders_period_summary', 'description' => 'Сравнява поръчките за относителен или конкретен период. Заявката може да бъде днес, вчера, тази седмица, миналата седмица, този месец, миналия месец или YYYY-MM-DD до YYYY-MM-DD.'],
            ['name' => 'invoices_period_summary', 'description' => 'Обобщава фактурите за относителен или конкретен период по дата на издаване.'],
            ['name' => 'find_products', 'description' => 'Намира продукти по име, SKU, адрес или идентификатор. Използвай при конкретно търсене на продукт.'],
            ['name' => 'find_orders', 'description' => 'Намира поръчки по номер, клиентско име, имейл или идентификатор.'],
            ['name' => 'find_invoices', 'description' => 'Намира фактури и кредитни известия по номер или идентификатор.'],
            ['name' => 'find_categories', 'description' => 'Намира категории по име, адрес или идентификатор.'],
            ['name' => 'category_overview', 'description' => 'Списък на активните категории с брой продукти.'],
        ]);
    }

    /** @return array<string, mixed> */
    private function execute(string $name, array $arguments): array
    {
        return match ($name) {
            'store_overview' => ['active_products' => Product::query()->where('is_active', true)->count(), 'active_categories' => Category::query()->where('is_active', true)->count(), 'orders' => Order::query()->count(), 'invoices' => Invoice::query()->where('type', 'invoice')->count()],
            'low_stock_products' => $this->lowStock(),
            'recent_orders' => ['orders' => Order::query()->latest('id')->limit(10)->get(['id', 'number', 'first_name', 'last_name', 'status', 'total', 'created_at'])->map(static fn (Order $order): array => ['id' => $order->id, 'number' => $order->number, 'customer' => trim($order->first_name . ' ' . $order->last_name), 'status' => $order->status, 'total' => (float) $order->total, 'created_at' => $order->created_at?->toIso8601String()])->all()],
            'date_context' => $this->dateContext(),
            'orders_period_summary' => $this->ordersPeriodSummary((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'invoices_period_summary' => $this->invoicesPeriodSummary((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'find_products' => $this->findProducts((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'find_orders' => $this->findOrders((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'find_invoices' => $this->findInvoices((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'find_categories' => $this->findCategories((string) ($arguments['query'] ?? $arguments['request'] ?? '')),
            'category_overview' => ['categories' => Category::query()->where('is_active', true)->withCount('products')->orderBy('name')->get(['id', 'name'])->map(static fn (Category $category): array => ['id' => $category->id, 'name' => $category->name, 'products' => (int) $category->products_count])->all()],
            default => ['error' => 'Непозволена справка.'],
        };
    }

    /** @return array<string, mixed> */
    private function lowStock(): array
    {
        $threshold = max(0, (int) (\App\Models\SiteSetting::query()->value('low_stock_threshold') ?? 5));
        $variants = ProductVariant::query()->with('product')->where('is_active', true)->where('stock', '<=', $threshold)->limit(50)->get();
        return ['threshold' => $threshold, 'items' => $variants->map(static fn (ProductVariant $variant): array => ['product' => $variant->product?->name, 'variant' => $variant->name, 'stock' => (int) $variant->stock])->all()];
    }

    /** @return array<string, mixed> */
    private function findProducts(string $query): array
    {
        $query = trim($query); $builder = Product::query()->with('category')->where(function ($q) use ($query): void { $like = '%' . addcslashes($query, '%_\\') . '%'; $q->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('slug', 'like', $like); if (ctype_digit($query)) $q->orWhereKey((int) $query); });
        $items = $builder->limit(20)->get(['id', 'name', 'sku', 'slug', 'category_id', 'is_active'])->map(static fn (Product $product): array => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'slug' => $product->slug, 'category' => $product->category?->name, 'active' => (bool) $product->is_active])->all();
        return ['query' => $query, 'products' => $items, '_links' => array_map(static fn (array $item): array => ['label' => 'Продукт: ' . $item['name'], 'to' => '/products/' . $item['id']], $items)];
    }

    /** @return array<string, mixed> */
    private function findOrders(string $query): array
    {
        $query = trim($query); $builder = Order::query()->where(function ($q) use ($query): void { $like = '%' . addcslashes($query, '%_\\') . '%'; $q->where('number', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like); if (ctype_digit($query)) $q->orWhereKey((int) $query); });
        $items = $builder->latest('id')->limit(20)->get(['id', 'number', 'first_name', 'last_name', 'email', 'status', 'total'])->map(static fn (Order $order): array => ['id' => $order->id, 'number' => $order->number, 'customer' => trim($order->first_name . ' ' . $order->last_name), 'email' => $order->email, 'status' => $order->status, 'total' => (float) $order->total])->all();
        return ['query' => $query, 'orders' => $items, '_links' => array_map(static fn (array $item): array => ['label' => 'Поръчка: ' . $item['number'], 'to' => '/orders/' . $item['id']], $items)];
    }

    /** @return array<string, mixed> */
    private function findInvoices(string $query): array
    {
        $query = trim($query); $builder = Invoice::query()->where('number', 'like', '%' . addcslashes($query, '%_\\') . '%'); if (ctype_digit($query)) $builder->orWhereKey((int) $query); $items = $builder->latest('id')->limit(20)->get(['id', 'number', 'type', 'status', 'total_gross'])->map(static fn (Invoice $invoice): array => ['id' => $invoice->id, 'number' => $invoice->number, 'type' => $invoice->type, 'status' => $invoice->status, 'total' => (float) $invoice->total_gross])->all();
        return ['query' => $query, 'documents' => $items, '_links' => array_map(static fn (array $item): array => ['label' => ($item['type'] === 'credit_note' ? 'Кредитно известие: ' : 'Фактура: ') . ($item['number'] ?? $item['id']), 'to' => ($item['type'] === 'credit_note' ? '/credit-notes/' : '/invoices/') . $item['id']], $items)];
    }

    /** @return array<string, mixed> */
    private function findCategories(string $query): array
    {
        $query = trim($query); $like = '%' . addcslashes($query, '%_\\') . '%'; $builder = Category::query()->where(static fn ($q) => $q->where('name', 'like', $like)->orWhere('slug', 'like', $like)); if (ctype_digit($query)) $builder->orWhereKey((int) $query); $items = $builder->limit(20)->get(['id', 'name', 'slug', 'parent_id'])->map(static fn (Category $category): array => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug, 'parent_id' => $category->parent_id])->all();
        return ['query' => $query, 'categories' => $items, '_links' => array_map(static fn (array $item): array => ['label' => 'Категория: ' . $item['name'], 'to' => '/categories/' . $item['id']], $items)];
    }

    /** @return array<string, mixed> */
    private function dateContext(): array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Sofia'));
        $monday = $today->modify('monday this week');
        $lastMonday = $monday->modify('-7 days');
        $thisMonth = $today->modify('first day of this month');
        $lastMonth = $thisMonth->modify('-1 month');
        return ['timezone' => 'Europe/Sofia', 'today' => $today->format('Y-m-d'), 'yesterday' => $today->modify('-1 day')->format('Y-m-d'), 'this_week' => [$monday->format('Y-m-d'), $today->format('Y-m-d')], 'last_week' => [$lastMonday->format('Y-m-d'), $lastMonday->modify('+6 days')->format('Y-m-d')], 'this_month' => [$thisMonth->format('Y-m-d'), $today->format('Y-m-d')], 'last_month' => [$lastMonth->format('Y-m-d'), $thisMonth->modify('-1 day')->format('Y-m-d')]];
    }

    /** @return array<string, mixed> */
    private function ordersPeriodSummary(string $period): array
    {
        [$from, $to, $label] = $this->resolvePeriod($period);
        $orders = Order::query()->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->get(['id', 'number', 'status', 'total']);
        return ['period' => $label, 'date_from' => $from, 'date_to' => $to, 'orders_count' => $orders->count(), 'total' => round((float) $orders->sum('total'), 2), 'paid_orders' => $orders->where('status', 'paid')->count(), 'cancelled_orders' => $orders->where('status', 'cancelled')->count(), 'orders' => $orders->map(static fn (Order $order): array => ['id' => $order->id, 'number' => $order->number, 'status' => $order->status, 'total' => (float) $order->total])->values()->all()];
    }

    /** @return array<string, mixed> */
    private function invoicesPeriodSummary(string $period): array
    {
        [$from, $to, $label] = $this->resolvePeriod($period);
        $invoices = Invoice::query()->where('type', 'invoice')->whereBetween('issue_date', [$from, $to])->get(['id', 'number', 'status', 'total_gross']);
        return ['period' => $label, 'date_from' => $from, 'date_to' => $to, 'invoices_count' => $invoices->count(), 'total' => round((float) $invoices->sum('total_gross'), 2), 'invoices' => $invoices->map(static fn (Invoice $invoice): array => ['id' => $invoice->id, 'number' => $invoice->number, 'status' => $invoice->status, 'total' => (float) $invoice->total_gross])->values()->all()];
    }

    /** @return array{0:string,1:string,2:string} */
    private function resolvePeriod(string $period): array
    {
        $period = mb_strtolower(trim($period)); $today = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Sofia'));
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s*(?:до|to|-)\s*(\d{4}-\d{2}-\d{2})$/u', $period, $match) === 1) return [$match[1], $match[2], $match[1] . ' – ' . $match[2]];
        if (str_contains($period, 'вчера')) { $day = $today->modify('-1 day')->format('Y-m-d'); return [$day, $day, 'вчера']; }
        if (str_contains($period, 'днес')) { $day = $today->format('Y-m-d'); return [$day, $day, 'днес']; }
        if (str_contains($period, 'миналата седмица')) { $start = $today->modify('monday this week')->modify('-7 days'); return [$start->format('Y-m-d'), $start->modify('+6 days')->format('Y-m-d'), 'миналата седмица']; }
        if (str_contains($period, 'седмиц')) return [$today->modify('monday this week')->format('Y-m-d'), $today->format('Y-m-d'), 'тази седмица'];
        if (str_contains($period, 'миналия месец')) { $start = $today->modify('first day of last month'); return [$start->format('Y-m-d'), $start->modify('last day of this month')->format('Y-m-d'), 'миналия месец']; }
        if (str_contains($period, 'месец')) return [$today->modify('first day of this month')->format('Y-m-d'), $today->format('Y-m-d'), 'този месец'];
        $day = $today->format('Y-m-d'); return [$day, $day, 'днес'];
    }

    /** @param array<string, mixed> $response @return list<array{name:string,call_id:string,arguments:array<string,mixed>}> */
    private function functionCalls(array $response): array
    {
        $calls = [];
        foreach (($response['output'] ?? []) as $item) if (($item['type'] ?? '') === 'function_call') $calls[] = ['name' => (string) ($item['name'] ?? ''), 'call_id' => (string) ($item['call_id'] ?? ''), 'arguments' => is_string($item['arguments'] ?? null) ? (json_decode($item['arguments'], true) ?: []) : []];
        return $calls;
    }

    private function text(array $response): string
    {
        if (is_string($response['output_text'] ?? null)) return trim($response['output_text']);
        foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $part) if (($part['type'] ?? '') === 'output_text') return trim((string) ($part['text'] ?? ''));
        return '';
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function request(array $payload, string $key): array
    {
        $curl = curl_init((string) $this->config['api_url'] . '/responses');
        if ($curl === false) throw new AuthException('AI услугата не може да бъде стартирана.', 503);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $key], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => (int) $this->config['timeout_seconds']]);
        $raw = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || $status < 200 || $status >= 300) {
            error_log('OpenAI admin assistant rejected [status=' . $status . ', error=' . json_encode($data['error'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ']');
            throw new AuthException('AI справката не можа да бъде генерирана.', 502);
        }
        return $data;
    }

    /** @return list<array{label:string,to:string}> */
    private function links(string $message): array
    {
        $text = mb_strtolower($message);
        if (str_contains($text, 'категор')) return [['label' => 'Категории', 'to' => '/categories']];
        if (str_contains($text, 'извест')) return [['label' => 'Известия', 'to' => '/notifications']];
        if (str_contains($text, 'поръч')) return [['label' => 'Поръчки', 'to' => '/orders']];
        if (str_contains($text, 'фактур')) return [['label' => 'Фактури', 'to' => '/invoices']];
        if (str_contains($text, 'счетов')) return [['label' => 'Счетоводство', 'to' => '/accounting']];
        if (str_contains($text, 'отчет')) return [['label' => 'Отчети', 'to' => '/reports']];
        if (str_contains($text, 'съобщ') || str_contains($text, 'запит')) return [['label' => 'Съобщения', 'to' => '/messages']];
        if (str_contains($text, 'банер')) return [['label' => 'Банери', 'to' => '/banners']];
        if (str_contains($text, 'страниц')) return [['label' => 'Страници', 'to' => '/pages']];
        if (str_contains($text, 'меди')) return [['label' => 'Медия', 'to' => '/media']];
        if (str_contains($text, 'потреб')) return [['label' => 'Потребители', 'to' => '/users']];
        if (str_contains($text, 'настрой')) return [['label' => 'Настройки', 'to' => '/settings']];
        if (str_contains($text, 'персонализ')) return [['label' => 'Персонализиране', 'to' => '/customization']];
        if (str_contains($text, 'продукт') || str_contains($text, 'налич')) return [['label' => 'Продукти', 'to' => '/products']];
        return [['label' => 'Табло', 'to' => '/']];
    }

    /** @param list<array{label:string,to:string}> $current @param list<array{label:string,to:string}> $additional @return list<array{label:string,to:string}> */
    private function mergeLinks(array $current, array $additional): array
    {
        $links = [];
        foreach (array_merge($current, $additional) as $link) {
            if (!is_array($link) || !is_string($link['label'] ?? null) || !is_string($link['to'] ?? null)) continue;
            $links[$link['to']] = ['label' => $link['label'], 'to' => $link['to']];
        }
        return array_values(array_slice($links, 0, 8, true));
    }

    private function knowledge(): string
    {
        return implode("\n", [
            'НАВИГАЦИЯ И ОСНОВНИ ДЕЙСТВИЯ',
            '• Табло (/): общ преглед на магазина, реални показатели, бързи връзки и последни активности.',
            '• Известия (/notifications): непрочетени известия, подробности, маркиране като прочетено, архивиране, изтриване и странициране.',
            '• Продажби: Поръчки (/orders) показва поръчки и филтрира по номер, име, имейл, статус, доставка, плащане и период; от реда се отварят детайлите и се променят статус/проследяване.',
            '• Продажби: Фактури (/invoices) е архив на фактури с филтри, преглед, PDF, издаване, анулиране и експорт за период.',
            '• Продажби: Кредитни известия (/credit-notes) са отделен раздел със собствен преглед, PDF и анулиране.',
            '• Каталог: Продукти (/products) търси и филтрира продукти; /products/new създава, /products/{id}/edit редактира, а /products/{id} показва продукт.',
            '• В редакция на продукт секция „Общи данни“ съдържа име, адрес, SKU, цена, описания, SEO и статус.',
            '• В редакция на продукт „Изображения“ управлява основна снимка и галерия; „Шаблон за атрибути“ прилага готови параметри, опции и варианти.',
            '• В редакция на продукт „Параметри“ са характеристики; „Опции“ са размери/цветове; „Варианти“ са комбинации с цена, наличност, SKU, статус и изображение; има масова промяна и прибиране/разгъване.',
            '• В редакция на продукт „Персонализация“ настройва текстовите полета, които клиентът попълва преди покупка.',
            '• Каталог: Категории (/categories) управлява дървото, родителските категории, активност, ред, изображения, изтриване и възстановяване.',
            '• Каталог: Медия (/media) качва, търси, редактира име/описание, копира адрес, изтегля и изтрива файлове.',
            '• Съдържание: Страници (/pages) управлява CMS страници, дърво, шаблони, текстов редактор, SEO и персонални полета.',
            '• Съдържание: Банери (/banners) управлява текст, адрес, кратък код, дизайн, размер, височина, ширина, фонова снимка, позиция, подравняване и незадължителни бутони.',
            '• Съдържание: Кампании (/campaigns) е предвиден раздел за промоции и купони.',
            '• Комуникация: Съобщения (/messages) показва запитвания, подробности, отговори и прикачени файлове.',
            '• Анализи: Отчети (/reports) генерира и пази месечни отчети за продажби, платени поръчки, продукти и признат приход.',
            '• Счетоводство (/accounting) има периоди, справки, CSV/Excel, PDF фактури, счетоводен пакет, плащания, възстановявания, Econt сверяване, месечно приключване и audit log.',
            '• Счетоводство: „Начислено ДДС“ се вижда само когато магазинът работи с ДДС; настройката се проверява в Настройки.',
            '• Потребители (/users) управлява администраторски и клиентски профили, роли, активност, пароли и аватари.',
            '• Настройки (/settings) управлява магазин, фирмени данни, ДДС, минимална наличност, Econt, достъп и други системни настройки.',
            '• Персонализиране (/customization) управлява фона на администрацията, изображения, градиенти и нивото на затъмняване.',
            '',
            'ПРАВИЛА ЗА ОТГОВОР',
            '• За въпрос „как да“ опиши кратки номерирани стъпки с точните български имена на менюта и секции.',
            '• За въпрос „къде е“ посочи раздела и добави директен навигационен линк, когато е възможно.',
            '• За числа, наличности, конкретни продукти, поръчки, категории и документи използвай read-only инструмент, а не тази карта.',
            '• Ако функцията е само предвидена или още не е реализирана, кажи това ясно и не измисляй интерфейс.',
        ]);
    }
}
