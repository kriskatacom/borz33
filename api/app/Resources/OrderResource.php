<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Order;
use App\Models\OrderItem;

class OrderResource
{
    /** @return array<string, mixed> */
    public static function toListArray(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'customer_name' => trim((string) $order->first_name . ' ' . (string) $order->last_name),
            'email' => $order->email,
            'phone' => $order->phone,
            'delivery_method' => $order->delivery_method,
            'payment_method' => $order->payment_method,
            'currency' => $order->currency,
            'total' => $order->total,
            'items_count' => (int) ($order->items_count ?? 0),
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public static function toArray(Order $order): array
    {
        return array_merge(self::toListArray($order), [
            'user_id' => $order->user_id,
            'first_name' => $order->first_name,
            'last_name' => $order->last_name,
            'subtotal' => $order->subtotal,
            'shipping_amount' => $order->shipping_amount,
            'vat_enabled' => (bool) $order->vat_enabled,
            'vat_rate' => $order->vat_rate,
            'address_line' => $order->address_line,
            'city' => $order->city,
            'postal_code' => $order->postal_code,
            'country' => $order->country,
            'econt_office_code' => $order->econt_office_code,
            'shipping_payer' => $order->shipping_payer,
            'econt_quote_snapshot' => $order->econt_quote_snapshot,
            'tracking_number' => $order->tracking_number,
            'tracking_url' => self::trackingUrl((string) ($order->tracking_number ?? '')),
            'shipped_at' => $order->shipped_at?->toIso8601String(),
            'notes' => $order->notes,
            'invoice_requested' => (bool) $order->invoice_requested,
            'invoice_company' => $order->invoice_company,
            'invoice_eik' => $order->invoice_eik,
            'invoice_vat_number' => $order->invoice_vat_number,
            'invoice_address' => $order->invoice_address,
            'invoice_mol' => $order->invoice_mol,
            'invoices' => $order->relationLoaded('invoices') ? $order->invoices->map(static fn ($invoice): array => ['id' => $invoice->id, 'number' => $invoice->number, 'type' => $invoice->type, 'status' => $invoice->status])->values()->all() : [],
            'items' => $order->items->map(static fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'options' => $item->options,
                'notes' => $item->notes,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'product_image_url' => $item->product?->frontImage?->path ? '/' . ltrim((string) $item->product->frontImage->path, '/') : null,
            ])->values()->all(),
        ]);
    }

    public static function trackingUrl(string $number): ?string
    {
        $number = trim($number);
        if ($number === '') return null;

        return (new \App\Services\Shipping\EcontConfigurationService())->trackingUrl($number);
    }
}
