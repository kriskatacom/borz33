<?php

declare(strict_types=1);

/** @var \App\Models\Order $order */

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => number_format((float) $value, 2, ',', ' ') . ' €';
$payment = $order->payment_method === 'bank_transfer' ? 'Банков превод' : 'Наложен платеж';
$delivery = $order->delivery_method === 'office' ? 'До офис на куриер' : 'До личен адрес';
$address = implode(', ', array_filter([
    (string) $order->address_line,
    (string) $order->city,
    (string) ($order->postal_code ?? ''),
    (string) $order->country,
]));
$vatEnabled = (bool) $order->vat_enabled;
$vatRate = (float) $order->vat_rate;
$vatAmount = $vatEnabled && $vatRate > 0 ? round((float) $order->total - (float) $order->total / (1 + $vatRate / 100), 2) : 0.0;
?>
<p style="margin:0 0 8px;font-size:20px;">Нова поръчка <?= $escape($order->number) ?></p>
<p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#3f3a34;">
    Получена е нова поръчка от онлайн магазина.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;background:#f6f1e7;border-radius:10px;">
    <tr>
        <td style="padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.75;color:#3f3a34;">
            <strong>Клиент:</strong> <?= $escape(trim($order->first_name . ' ' . $order->last_name)) ?><br>
            <strong>Имейл:</strong> <a href="mailto:<?= $escape($order->email) ?>" style="color:#173f32;"><?= $escape($order->email) ?></a><br>
            <strong>Телефон:</strong> <a href="tel:<?= $escape($order->phone) ?>" style="color:#173f32;"><?= $escape($order->phone) ?></a><br>
            <strong>Доставка:</strong> <?= $escape($delivery) ?><br>
            <strong>Адрес:</strong> <?= $escape($address) ?><br>
            <strong>Плащане:</strong> <?= $escape($payment) ?>
        </td>
    </tr>
</table>

<p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:0.1em;text-transform:uppercase;color:#6b645b;">Продукти</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
    <?php foreach ($order->items as $item): ?>
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #e4ddd0;font-family:Arial,Helvetica,sans-serif;">
                <p style="margin:0;font-size:14px;font-weight:bold;"><?= (int) $item->qty ?> × <?= $escape($item->name) ?></p>
                <?php if ($item->sku): ?><p style="margin:4px 0 0;font-size:12px;color:#6b645b;">Код: <?= $escape($item->sku) ?></p><?php endif; ?>
                <?php if ($item->options): ?><p style="margin:4px 0 0;font-size:12px;color:#6b645b;"><?= $escape($item->options) ?></p><?php endif; ?>
                <?php if ($item->notes): ?><p style="margin:4px 0 0;font-size:12px;color:#6b645b;"><?= nl2br($escape($item->notes)) ?></p><?php endif; ?>
            </td>
            <td align="right" style="padding:12px 0 12px 16px;border-bottom:1px solid #e4ddd0;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;white-space:nowrap;"><?= $escape($money($item->total)) ?></td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td style="padding:16px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;">Продукти</td>
        <td align="right" style="padding:16px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;"><?= $escape($money($order->subtotal)) ?></td>
    </tr>
    <tr>
        <td style="padding:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;">Доставка с Еконт</td>
        <td align="right" style="padding:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;"><?= $escape($money($order->shipping_amount)) ?></td>
    </tr>
    <tr>
        <td style="padding:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;">ДДС<?= $vatEnabled ? ' (' . $escape(number_format($vatRate, 0)) . '%)' : '' ?></td>
        <td align="right" style="padding:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;"><?= $vatEnabled ? $escape($money($vatAmount)) : 'Не се начислява' ?></td>
    </tr>
    <tr>
        <td style="padding:10px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;">Общо</td>
        <td align="right" style="padding:10px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;"><?= $escape($money($order->total)) ?></td>
    </tr>
</table>

<?php if ($order->notes): ?>
    <div style="margin:0;padding:14px 16px;border-left:3px solid #173f32;background:#eef2ed;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#3f3a34;">
        <strong>Бележка от клиента:</strong><br><?= nl2br($escape($order->notes)) ?>
    </div>
<?php endif; ?>
