<?php

declare(strict_types=1);

/** @var \App\Models\Order $order */
/** @var array<string, string> $company */
/** @var string $statusTitle */
/** @var string $statusMessage */
/** @var string $statusNote */
/** @var string $status */
/** @var string|null $trackingUrl */

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $value): string => number_format((float) $value, 2, ',', ' ') . ' €';
$website = rtrim((string) ($company['website'] ?? ''), '/');
$accent = $status === 'cancelled' ? '#8f2f2f' : '#173f32';
?>
<p style="margin:0 0 8px;font-size:18px;">Здравейте, <?= $escape($order->first_name) ?>.</p>
<p style="margin:0 0 22px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;"><?= $escape($statusMessage) ?></p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #e4ddd0;border-left:4px solid <?= $escape($accent) ?>;">
    <tr>
        <td style="padding:18px 20px;font-family:Arial,Helvetica,sans-serif;">
            <p style="margin:0 0 6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#6b645b;">Нов статус</p>
            <p style="margin:0;font-size:18px;font-weight:bold;color:#1c1917;"><?= $escape($statusTitle) ?></p>
        </td>
    </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;background:#f6f1e7;">
    <tr><td style="padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6b645b;">Поръчка</td><td align="right" style="padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;"><?= $escape($order->number) ?></td></tr>
    <tr><td style="padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6b645b;">Обща стойност</td><td align="right" style="padding:10px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;"><?= $escape($money($order->total)) ?></td></tr>
</table>

<?php if ($order->tracking_number && $trackingUrl): ?>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #d8e4dc;background:#eef2ed;">
        <tr><td style="padding:16px;font-family:Arial,Helvetica,sans-serif;"><p style="margin:0 0 5px;font-size:12px;color:#6b645b;">Номер на товарителница</p><p style="margin:0 0 14px;font-size:17px;font-weight:bold;letter-spacing:.04em;"><?= $escape($order->tracking_number) ?></p><a href="<?= $escape($trackingUrl) ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 16px;background:#173f32;color:#fffdf8;font-size:13px;font-weight:bold;text-decoration:none;">Проследете пратката</a></td></tr>
    </table>
<?php endif; ?>

<p style="margin:0 0 22px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#3f3a34;"><?= $escape($statusNote) ?></p>

<?php if ($order->user_id && $website !== ''): ?>
    <p style="margin:0;text-align:center;"><a href="<?= $escape($website . '/account/orders') ?>" style="display:inline-block;padding:12px 20px;background:#173f32;color:#fffdf8;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;text-decoration:none;">Вижте поръчките си</a></p>
<?php endif; ?>
