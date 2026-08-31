<?php

declare(strict_types=1);

use Store\Services\ProductPage;

/** @var \App\Models\Order $order */
/** @var bool $customerEmailSent */

$customerEmailSent = (bool) ($customerEmailSent ?? false);
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$payment = $order->payment_method === 'bank_transfer' ? 'Банков превод' : 'Наложен платеж';
$delivery = match ($order->delivery_method) { 'office' => 'До офис на куриер', 'machine' => 'До Еконтомат', default => 'До личен адрес' };
$address = implode(', ', array_filter([
    (string) $order->address_line,
    (string) $order->city,
    (string) ($order->postal_code ?? ''),
    (string) $order->country,
]));
?>
<section class="store-order-success">
    <ol class="store-checkout-steps" aria-label="Стъпки на поръчката">
        <li class="is-done"><strong>Количка</strong></li>
        <li class="is-done"><strong>Детайли</strong></li>
        <li class="is-current" aria-current="step"><strong>Готово</strong></li>
    </ol>

    <div class="store-order-success-head">
        <span class="store-order-success-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>
        </span>
        <p>Поръчката е приета</p>
        <h1>Благодарим ви!</h1>
        <?php if ($customerEmailSent): ?>
            <p>Изпратихме потвърждение на <strong><?= $escape($order->email) ?></strong>. Ще се свържем с вас при обработката.</p>
        <?php else: ?>
            <p>Поръчката е записана успешно. Не успяхме да изпратим потвърждение на <strong><?= $escape($order->email) ?></strong>, но ще се свържем с вас при обработката.</p>
        <?php endif; ?>
    </div>

    <div class="store-order-success-details">
        <div><span>Номер на поръчка</span><strong><?= $escape($order->number) ?></strong></div>
        <div><span>Общо с доставка</span><strong><?= $escape(ProductPage::money($order->total)) ?></strong></div>
        <div><span>Плащане</span><strong><?= $escape($payment) ?></strong></div>
    </div>

    <div class="store-order-success-layout">
        <section class="store-order-success-card" aria-labelledby="success-items-title">
            <div class="store-order-success-card-head">
                <div>
                    <p>Преглед</p>
                    <h2 id="success-items-title">Поръчани продукти</h2>
                </div>
                <span><?= (int) $order->items->sum('qty') ?> бр.</span>
            </div>
            <ul class="store-order-success-items">
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
        </section>

        <section class="store-order-success-card" aria-labelledby="success-delivery-title">
            <div class="store-order-success-card-head">
                <div>
                    <p>Получаване</p>
                    <h2 id="success-delivery-title">Доставка</h2>
                </div>
            </div>
            <dl class="store-order-success-delivery">
                <div><dt>Начин</dt><dd><?= $escape($delivery) ?></dd></div>
                <div><dt>Адрес</dt><dd><?= $escape($address) ?></dd></div>
                <div><dt>Цена</dt><dd><?= $escape(ProductPage::money($order->shipping_amount)) ?></dd></div>
                <div><dt>Получател</dt><dd><?= $escape(trim($order->first_name . ' ' . $order->last_name)) ?></dd></div>
                <div><dt>Телефон</dt><dd><?= $escape($order->phone) ?></dd></div>
            </dl>
        </section>
    </div>

    <?php if ($order->payment_method === 'bank_transfer'): ?>
        <p class="store-order-success-notice">
            <span aria-hidden="true">i</span>
            Ще се свържем с вас с данните за банковия превод преди обработката на поръчката.
        </p>
    <?php endif; ?>

    <p class="store-order-success-shipping"><?= $order->shipping_payer === 'sender' ? 'Магазинът поема изчислената цена за доставка.' : 'Към поръчката е добавена цената, изчислена в тестовата среда на Econt.' ?></p>
    <div class="store-order-success-actions">
        <a href="/catalog">Продължете пазаруването</a>
        <?php if ($order->user_id): ?>
            <a href="/account/orders">Моите поръчки</a>
        <?php else: ?>
            <a href="/">Към началната страница</a>
        <?php endif; ?>
    </div>
</section>
