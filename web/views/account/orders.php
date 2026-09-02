<?php

declare(strict_types=1);

use Store\Services\ProductPage;
use Store\Core\Html;
use App\Resources\OrderResource;
use App\Resources\ProductImageResource;

/** @var \Illuminate\Support\Collection<int, \App\Models\Order> $orders */
/** @var int $orderCount */
/** @var array{page?: int, lastPage?: int, status?: string, filteredCount?: int} $ordersPagination */

$orders = $orders ?? collect();
$orderCount = (int) ($orderCount ?? $orders->count());
$ordersPagination = $ordersPagination ?? ['page' => 1, 'lastPage' => 1];
$page = max(1, (int) ($ordersPagination['page'] ?? 1));
$lastPage = max(1, (int) ($ordersPagination['lastPage'] ?? 1));
$selectedStatus = (string) ($ordersPagination['status'] ?? 'all');
$filteredCount = (int) ($ordersPagination['filteredCount'] ?? $orderCount);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = [
    'pending' => 'Очаква обработка',
    'confirmed' => 'Потвърдена',
    'paid' => 'Платена',
    'processing' => 'Обработва се',
    'shipped' => 'Изпратена',
    'delivered' => 'Доставена',
    'cancelled' => 'Отказана',
];
?>
<div class="store-account-orders-head">
    <p><?= $filteredCount ?> <?= $filteredCount === 1 ? 'поръчка' : 'поръчки' ?><?= $selectedStatus === 'all' ? ' в историята' : ' с избрания статус' ?></p>
    <a href="/catalog">Нова поръчка</a>
</div>
<form class="store-account-order-filter" method="get">
    <label for="account-order-status">Статус</label>
    <select id="account-order-status" name="status" onchange="this.form.submit()">
        <option value="all"<?= $selectedStatus === 'all' ? ' selected' : '' ?>>Всички</option>
        <?php foreach ($statuses as $value => $label): ?><option value="<?= $escape($value) ?>"<?= $selectedStatus === $value ? ' selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?>
    </select>
    <noscript><button type="submit">Филтрирай</button></noscript>
</form>

<?php if ($orders->isEmpty()): ?>
    <article class="store-card store-account-orders-empty" role="status">
        <div class="flex items-start gap-3">
            <span class="store-shortcut-icon"><?= Html::iconSvg('package') ?></span>
            <div>
                <?php if ($orderCount === 0): ?>
                    <strong class="store-account-orders-empty-title">Все още нямате поръчки</strong>
                    <p class="m-0 text-sm text-muted">Когато направите поръчка, нейните детайли и статус ще се показват тук.</p>
                    <p class="mb-0 mt-4"><a class="font-semibold" href="/catalog">Разгледайте каталога</a></p>
                <?php else: ?>
                    <strong class="store-account-orders-empty-title">Няма поръчки със статус „<?= $escape($statuses[$selectedStatus] ?? 'Избран') ?>“</strong>
                    <p class="m-0 text-sm text-muted">Изберете друг статус или вижте всички поръчки.</p>
                    <p class="mb-0 mt-4"><a class="font-semibold" href="/account/orders?status=all">Покажи всички поръчки</a></p>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php else: ?>

    <div class="store-account-orders">
        <?php foreach ($orders as $order): ?>
            <?php
            $status = $statuses[(string) $order->status] ?? 'В обработка';
            $date = $order->created_at?->timezone('Europe/Sofia')->format('d.m.Y, H:i') ?? '';
            $delivery = match ($order->delivery_method) { 'office' => 'До офис на куриер', 'machine' => 'До Еконтомат', default => 'До личен адрес' };
            ?>
            <article class="store-account-order">
                <header>
                    <div>
                        <p>Поръчка</p>
                        <h3><?= $escape($order->number) ?></h3>
                    </div>
                    <span class="store-account-order-status" data-status="<?= $escape($order->status) ?>"><?= $escape($status) ?></span>
                </header>

                <div class="store-account-order-meta">
                    <div><span>Дата</span><strong><?= $escape($date) ?></strong></div>
                    <div><span>Продукти</span><strong><?= (int) $order->items->sum('qty') ?> бр.</strong></div>
                    <div><span>Общо</span><strong><?= $escape(ProductPage::money($order->total)) ?></strong></div>
                </div>

                <details>
                    <summary>
                        <span>Детайли за поръчката</span>
                        <span aria-hidden="true"><?= Html::iconSvg('chevron-down') ?></span>
                    </summary>
                    <div class="store-account-order-details">
                        <ul>
                            <?php foreach ($order->items as $item): ?>
                                <?php
                                $itemProduct = $item->product;
                                $itemImage = $itemProduct?->frontImage;
                                $itemImageData = $itemImage !== null ? ProductImageResource::toArray($itemImage) : null;
                                ?>
                                <li>
                                    <span><?= (int) $item->qty ?>×</span>
                                    <?php if ($itemProduct !== null): ?>
                                        <a class="store-account-order-product" href="/products/<?= $escape($itemProduct->slug) ?>">
                                            <span class="store-account-order-product-image">
                                                <?php if ($itemImageData !== null): ?><img src="<?= $escape($itemImageData['url']) ?>" alt="" width="56" height="56" loading="lazy"><?php else: ?><?= Html::iconSvg('package') ?><?php endif; ?>
                                            </span>
                                            <span class="store-account-order-product-copy">
                                                <strong><?= $escape($item->name) ?></strong>
                                                <?php if ($item->options): ?><small><?= $escape($item->options) ?></small><?php endif; ?>
                                                <?php if ($item->notes): ?><small><?= nl2br($escape($item->notes)) ?></small><?php endif; ?>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <div class="store-account-order-product-copy">
                                            <strong><?= $escape($item->name) ?></strong>
                                            <?php if ($item->options): ?><small><?= $escape($item->options) ?></small><?php endif; ?>
                                            <?php if ($item->notes): ?><small><?= nl2br($escape($item->notes)) ?></small><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <b><?= $escape(ProductPage::money($item->total)) ?></b>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <dl>
                            <div><dt>Доставка</dt><dd><?= $escape($delivery) ?></dd></div>
                            <div><dt>Цена за доставка</dt><dd><?= $escape(ProductPage::money($order->shipping_amount)) ?></dd></div>
                            <div><dt>Адрес</dt><dd><?= $escape(implode(', ', array_filter([(string) $order->address_line, (string) $order->city, (string) ($order->postal_code ?? ''), (string) $order->country]))) ?></dd></div>
                            <div><dt>Плащане</dt><dd><?= $order->payment_method === 'bank_transfer' ? 'Банков превод' : 'Наложен платеж' ?></dd></div>
                            <?php if ($order->tracking_number): ?>
                                <div><dt>Товарителница</dt><dd><a href="<?= $escape(OrderResource::trackingUrl((string) $order->tracking_number)) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($order->tracking_number) ?> · Проследи</a></dd></div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </details>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if ($lastPage > 1): ?>
        <nav class="store-account-pagination" aria-label="Страници с поръчки">
            <?php if ($page > 1): ?><a href="/account/orders?page=<?= $page - 1 ?>&amp;status=<?= $escape($selectedStatus) ?>" aria-label="Предишна страница">←</a><?php endif; ?>
            <?php foreach (range(max(1, $page - 2), min($lastPage, $page + 2)) as $number): ?>
                <?php if ($number === $page): ?><span aria-current="page"><?= $number ?></span><?php else: ?><a href="/account/orders?page=<?= $number ?>&amp;status=<?= $escape($selectedStatus) ?>"><?= $number ?></a><?php endif; ?>
            <?php endforeach; ?>
            <?php if ($page < $lastPage): ?><a href="/account/orders?page=<?= $page + 1 ?>&amp;status=<?= $escape($selectedStatus) ?>" aria-label="Следваща страница">→</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
