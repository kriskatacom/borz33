<?php
/** @var \App\Models\Invoice $invoice */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$credit = $invoice->type === 'credit_note';
?>
<p style="margin:0 0 12px;font-size:18px;">Здравейте, <?= $escape($invoice->order?->first_name ?: ($invoice->buyer_snapshot['company'] ?? '')) ?>.</p>
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:#3f3a34;">Към този имейл прикачваме <?= $credit ? 'кредитно известие' : 'фактура' ?> № <strong><?= $escape($invoice->number) ?></strong> за поръчка <?= $escape($invoice->order?->number) ?>.</p>
