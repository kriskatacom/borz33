<?php

declare(strict_types=1);

/** @var \App\Models\Order $order */
/** @var array<string, string> $company */

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
<p style="margin:0 0 8px;font-size:18px;">Здравейте, <?= $escape($order->first_name) ?>.</p>
<p style="margin:0 0 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;">
    Благодарим Ви! Получихме поръчката и ще се свържем с Вас при обработката.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e4ddd0;border-radius:10px;">
    <tr>
        <td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6b645b;">Номер на поръчка</td>
        <td align="right" style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:#1c1917;"><?= $escape($order->number) ?></td>
    </tr>
</table>

<p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:0.1em;text-transform:uppercase;color:#6b645b;">Поръчани продукти</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
    <?php foreach ($order->items as $item): ?>
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #e4ddd0;font-family:Arial,Helvetica,sans-serif;">
                <p style="margin:0;font-size:14px;font-weight:bold;color:#1c1917;"><?= (int) $item->qty ?> × <?= $escape($item->name) ?></p>
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

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background:#f6f1e7;border-radius:10px;">
    <tr>
        <td style="padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.7;color:#3f3a34;">
            <strong>Доставка:</strong> <?= $escape($delivery) ?><br>
            <?= $escape($address) ?><br>
            <strong>Плащане:</strong> <?= $escape($payment) ?><br>
            <span style="color:#6b645b;">Към поръчката е добавена фиксираната цена за избрания начин на доставка.</span>
        </td>
    </tr>
</table>

<?php if ($order->payment_method === 'bank_transfer'): ?>
    <p style="margin:0 0 20px;padding:14px 16px;border-left:3px solid #173f32;background:#eef2ed;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#3f3a34;">
        Ще се свържем с Вас с данните за банковия превод.
    </p>
<?php endif; ?>

<?php if ($order->user_id): ?>
    <p style="margin:0;text-align:center;">
        <a href="<?= $escape(rtrim((string) ($company['website'] ?? ''), '/') . '/account/orders') ?>" style="display:inline-block;padding:12px 20px;border-radius:8px;background:#173f32;color:#fffdf8;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;text-decoration:none;">Вижте поръчките си</a>
    </p>
<?php endif; ?>
