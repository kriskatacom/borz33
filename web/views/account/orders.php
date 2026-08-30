<?php

declare(strict_types=1);

use Store\Services\ProductPage;
use Store\Core\Html;

/** @var \Illuminate\Support\Collection<int, \App\Models\Order> $orders */
/** @var int $orderCount */

$orders = $orders ?? collect();
$orderCount = (int) ($orderCount ?? $orders->count());
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statuses = [
    'pending' => 'Очаква обработка',
    'confirmed' => 'Потвърдена',
    'processing' => 'Обработва се',
    'shipped' => 'Изпратена',
    'delivered' => 'Доставена',
    'cancelled' => 'Отказана',
];
?>
<?php if ($orders->isEmpty()): ?>
    <article class="store-card">
        <div class="flex items-start gap-3">
            <span class="store-shortcut-icon"><?= Html::iconSvg('package') ?></span>
            <div>
                <h2>Все още няма поръчки</h2>
                <p class="m-0 text-sm text-muted">Когато поръчате, историята и статусите ще се показват тук.</p>
                <p class="mb-0 mt-4"><a class="font-semibold" href="/catalog">Към каталога</a></p>
            </div>
        </div>
    </article>
<?php else: ?>
    <div class="store-account-orders-head">
        <div>
            <h2>Вашите поръчки</h2>
            <p><?= $orderCount ?> <?= $orderCount === 1 ? 'поръчка' : 'поръчки' ?> в историята<?= $orderCount > 50 ? ' · показани са последните 50' : '' ?></p>
        </div>
        <a href="/catalog">Нова поръчка</a>
    </div>

    <div class="store-account-orders">
        <?php foreach ($orders as $order): ?>
            <?php
            $status = $statuses[(string) $order->status] ?? 'В обработка';
            $date = $order->created_at?->timezone('Europe/Sofia')->format('d.m.Y, H:i') ?? '';
            $delivery = $order->delivery_method === 'office' ? 'До офис на куриер' : 'До личен адрес';
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
                                <li>
                                    <span><?= (int) $item->qty ?>×</span>
                                    <div>
                                        <strong><?= $escape($item->name) ?></strong>
                                        <?php if ($item->options): ?><small><?= $escape($item->options) ?></small><?php endif; ?>
                                        <?php if ($item->notes): ?><small><?= nl2br($escape($item->notes)) ?></small><?php endif; ?>
                                    </div>
                                    <b><?= $escape(ProductPage::money($item->total)) ?></b>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <dl>
                            <div><dt>Доставка</dt><dd><?= $escape($delivery) ?></dd></div>
                            <div><dt>Цена за доставка</dt><dd><?= $escape(ProductPage::money($order->shipping_amount)) ?></dd></div>
                            <div><dt>Адрес</dt><dd><?= $escape(implode(', ', array_filter([(string) $order->address_line, (string) $order->city, (string) ($order->postal_code ?? ''), (string) $order->country]))) ?></dd></div>
                            <div><dt>Плащане</dt><dd><?= $order->payment_method === 'bank_transfer' ? 'Банков превод' : 'Наложен платеж' ?></dd></div>
                        </dl>
                    </div>
                </details>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
