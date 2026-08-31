<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Invoice;

final class InvoiceResource
{
    public static function toArray(Invoice $invoice): array
    {
        $creditedQuantities = [];
        $shippingCredited = false;
        if ($invoice->relationLoaded('creditNotes')) {
            foreach ($invoice->creditNotes->where('status', '!=', 'cancelled') as $credit) {
                $shippingCredited = $shippingCredited || abs((float) $credit->shipping_net) >= 0.01;
                foreach ($credit->items_snapshot as $item) {
                    if (!array_key_exists('source_index', $item)) continue;
                    $index = (int) $item['source_index'];
                    $creditedQuantities[$index] = ($creditedQuantities[$index] ?? 0) + abs((int) ($item['qty'] ?? 0));
                }
            }
        }
        $creditableItems = array_map(static fn (array $item, int $index): array => [
            'index' => $index,
            'remaining_qty' => max(0, (int) ($item['qty'] ?? 0) - (int) ($creditedQuantities[$index] ?? 0)),
        ], $invoice->items_snapshot, array_keys($invoice->items_snapshot));

        return ['id' => $invoice->id, 'order_id' => $invoice->order_id, 'order_number' => $invoice->order?->number, 'parent_invoice_id' => $invoice->parent_invoice_id, 'parent_invoice_number' => $invoice->parentInvoice?->number, 'type' => $invoice->type, 'number' => $invoice->number, 'status' => $invoice->status, 'issue_date' => $invoice->issue_date?->format('Y-m-d'), 'tax_event_date' => $invoice->tax_event_date?->format('Y-m-d'), 'currency' => $invoice->currency, 'seller' => $invoice->seller_snapshot, 'buyer' => $invoice->buyer_snapshot, 'payment' => $invoice->payment_snapshot, 'items' => $invoice->items_snapshot, 'creditable_items' => $creditableItems, 'shipping_creditable' => !$shippingCredited && abs((float) $invoice->shipping_net) >= 0.01, 'subtotal_net' => $invoice->subtotal_net, 'discount_net' => $invoice->discount_net, 'shipping_net' => $invoice->shipping_net, 'tax_amount' => $invoice->tax_amount, 'total_gross' => $invoice->total_gross, 'reason' => $invoice->reason, 'has_pdf' => $invoice->pdf_path !== null, 'issued_at' => $invoice->issued_at?->toIso8601String(), 'cancelled_at' => $invoice->cancelled_at?->toIso8601String(), 'created_at' => $invoice->created_at?->toIso8601String(), 'credit_notes' => $invoice->relationLoaded('creditNotes') ? $invoice->creditNotes->map(fn (Invoice $credit): array => ['id' => $credit->id, 'number' => $credit->number, 'status' => $credit->status, 'total_gross' => $credit->total_gross])->values()->all() : []];
    }
}
