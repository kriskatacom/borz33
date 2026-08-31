<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Capsule\Manager as Capsule;

final class InvoiceService
{
    public const STATUSES = ['draft', 'issued', 'cancelled', 'credited'];

    public function __construct(private readonly InvoicePdfService $pdf = new InvoicePdfService()) {}

    public function createForOrder(Order $order, bool $issue = true, ?int $createdBy = null): Invoice
    {
        $order->loadMissing(['items', 'invoices']);
        $existing = $order->invoices->firstWhere('type', 'invoice');
        if ($existing !== null) return $existing;

        $invoice = Capsule::connection()->transaction(function () use ($order, $issue, $createdBy): Invoice {
            $invoice = Invoice::query()->create($this->snapshot($order) + [
                'order_id' => $order->id,
                'type' => 'invoice',
                'status' => 'draft',
                'created_by' => $createdBy,
            ]);
            return $issue ? $this->issueInsideTransaction($invoice) : $invoice;
        });

        return $issue ? $this->persistPdf($invoice) : $invoice;
    }

    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') throw new ValidationException(['status' => ['Само чернова може да бъде издадена.']]);
        $invoice = Capsule::connection()->transaction(fn (): Invoice => $this->issueInsideTransaction($invoice));
        return $this->persistPdf($invoice);
    }

    public function credit(Invoice $invoice, string $reason, ?float $amount = null, ?int $createdBy = null): Invoice
    {
        if ($invoice->type !== 'invoice' || !in_array($invoice->status, ['issued', 'credited'], true)) throw new ValidationException(['invoice' => ['Кредитно известие може да се издаде само към издадена фактура.']]);
        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) throw new ValidationException(['reason' => ['Въведете основание между 3 и 500 знака.']]);
        $alreadyCredited = abs((float) $invoice->creditNotes()->where('status', '!=', 'cancelled')->sum('total_gross'));
        $remaining = round((float) $invoice->total_gross - $alreadyCredited, 2);
        $creditGross = $amount === null ? $remaining : round($amount, 2);
        if ($creditGross <= 0 || $creditGross > $remaining) throw new ValidationException(['amount' => ['Сумата трябва да бъде положителна и не по-голяма от оставащите ' . number_format($remaining, 2, '.', '') . ' EUR.']]);
        $ratio = $creditGross / (float) $invoice->total_gross;

        $credit = Capsule::connection()->transaction(function () use ($invoice, $reason, $creditGross, $ratio, $createdBy, $remaining): Invoice {
            $credit = Invoice::query()->create([
                'order_id' => $invoice->order_id, 'parent_invoice_id' => $invoice->id, 'type' => 'credit_note', 'status' => 'draft',
                'currency' => $invoice->currency, 'seller_snapshot' => $invoice->seller_snapshot, 'buyer_snapshot' => $invoice->buyer_snapshot, 'payment_snapshot' => $invoice->payment_snapshot,
                'items_snapshot' => array_map(static function (array $item) use ($ratio): array { foreach (['net_total', 'tax', 'gross_total'] as $key) $item[$key] = -round((float) ($item[$key] ?? 0) * $ratio, 2); return $item; }, $invoice->items_snapshot),
                'subtotal_net' => -round((float) $invoice->subtotal_net * $ratio, 2), 'discount_net' => -round((float) $invoice->discount_net * $ratio, 2),
                'shipping_net' => -round((float) $invoice->shipping_net * $ratio, 2), 'tax_amount' => -round((float) $invoice->tax_amount * $ratio, 2),
                'total_gross' => -$creditGross, 'reason' => $reason, 'created_by' => $createdBy,
            ]);
            $credit = $this->issueInsideTransaction($credit);
            if (abs($creditGross - $remaining) < 0.01) { $invoice->status = 'credited'; $invoice->save(); }
            return $credit;
        });
        return $this->persistPdf($credit);
    }

    public function cancel(Invoice $invoice, string $reason): Invoice
    {
        if (!in_array($invoice->status, ['draft', 'issued'], true)) throw new ValidationException(['status' => ['Документът не може да бъде анулиран в текущия статус.']]);
        $invoice->status = 'cancelled'; $invoice->reason = trim($reason) ?: 'Анулиран от администратор'; $invoice->cancelled_at = new \DateTimeImmutable(); $invoice->save();
        return $invoice->fresh(['order', 'parentInvoice', 'creditNotes']);
    }

    public function find(int $id): Invoice
    {
        $invoice = Invoice::query()->with(['order', 'parentInvoice', 'creditNotes'])->find($id);
        if ($invoice === null) throw new AuthException('Фактурата не е намерена.', 404);
        return $invoice;
    }

    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1)); $perPage = min(10000, max(10, (int) ($filters['per_page'] ?? 20)));
        $query = Invoice::query()->with('order');
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') { $like = '%' . addcslashes($q, '%_') . '%'; $query->where(function ($builder) use ($like): void { $builder->where('number', 'like', $like)->orWhere('buyer_snapshot->company', 'like', $like)->orWhere('buyer_snapshot->eik', 'like', $like)->orWhereHas('order', fn ($orders) => $orders->where('number', 'like', $like)->orWhere('email', 'like', $like)); }); }
        foreach (['status', 'type'] as $field) { $value = trim((string) ($filters[$field] ?? '')); if ($value !== '' && $value !== 'all') $query->where($field, $value); }
        if (($filters['date_from'] ?? '') !== '') $query->whereDate('issue_date', '>=', (string) $filters['date_from']);
        if (($filters['date_to'] ?? '') !== '') $query->whereDate('issue_date', '<=', (string) $filters['date_to']);
        $total = (clone $query)->count(); $items = $query->orderByDesc('issue_date')->orderByDesc('id')->forPage($page, $perPage)->get();
        return ['invoices' => $items, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => max(1, (int) ceil($total / $perPage))]];
    }

    private function snapshot(Order $order): array
    {
        $company = require dirname(__DIR__, 4) . '/config/company.php'; $rate = $order->vat_enabled ? max(0.0, (float) $order->vat_rate) : 0.0; $divisor = 1 + $rate / 100;
        $items = []; $itemsNet = 0.0; $itemsTax = 0.0;
        foreach ($order->items as $item) { $gross = round((float) $item->total, 2); $net = round($gross / $divisor, 2); $tax = round($gross - $net, 2); $itemsNet += $net; $itemsTax += $tax; $items[] = ['name' => (string) $item->name, 'sku' => (string) ($item->sku ?? ''), 'qty' => (int) $item->qty, 'unit_gross' => (float) $item->unit_price, 'net_total' => $net, 'tax_rate' => $rate, 'tax' => $tax, 'gross_total' => $gross]; }
        $shippingGross = round((float) $order->shipping_amount, 2); $shippingNet = round($shippingGross / $divisor, 2); $shippingTax = round($shippingGross - $shippingNet, 2);
        $paymentLabels = ['cash_on_delivery' => 'Наложен платеж', 'bank_transfer' => 'Банков превод', 'card' => 'Карта'];
        return ['currency' => $order->currency, 'seller_snapshot' => ['company' => $company['legal_name'], 'eik' => $company['eik'], 'vat_registered' => (bool) $order->vat_enabled, 'vat_rate' => $rate, 'vat_number' => $order->vat_enabled ? $company['vat'] : null, 'address' => trim($company['address'] . ', ' . $company['postal_code'] . ' ' . $company['city'] . ', ' . $company['country']), 'mol' => $company['mol']], 'buyer_snapshot' => ['company' => $order->invoice_company ?: trim($order->first_name . ' ' . $order->last_name), 'eik' => $order->invoice_eik, 'vat_number' => $order->invoice_vat_number, 'address' => $order->invoice_address ?: trim($order->address_line . ', ' . $order->postal_code . ' ' . $order->city . ', ' . $order->country), 'mol' => $order->invoice_mol, 'email' => $order->email], 'payment_snapshot' => ['method' => $order->payment_method, 'label' => $paymentLabels[$order->payment_method] ?? $order->payment_method], 'items_snapshot' => $items, 'subtotal_net' => round($itemsNet, 2), 'discount_net' => 0, 'shipping_net' => $shippingNet, 'tax_amount' => round($itemsTax + $shippingTax, 2), 'total_gross' => round((float) $order->total, 2)];
    }

    private function issueInsideTransaction(Invoice $invoice): Invoice
    {
        $sequence = Capsule::table('invoice_sequences')->where('name', 'fiscal_documents')->lockForUpdate()->first();
        if ($sequence === null) throw new \RuntimeException('Липсва последователност за фактурите.');
        $number = (int) $sequence->next_number; Capsule::table('invoice_sequences')->where('name', 'fiscal_documents')->update(['next_number' => $number + 1, 'updated_at' => date('Y-m-d H:i:s')]);
        $invoice->number = str_pad((string) $number, 10, '0', STR_PAD_LEFT); $invoice->status = 'issued'; $invoice->issue_date = date('Y-m-d'); $invoice->tax_event_date = date('Y-m-d'); $invoice->issued_at = new \DateTimeImmutable(); $invoice->save(); return $invoice->fresh(['order', 'parentInvoice', 'creditNotes']);
    }

    private function persistPdf(Invoice $invoice): Invoice
    {
        $invoice->pdf_path = $this->pdf->generate($invoice); $invoice->save(); return $invoice->fresh(['order', 'parentInvoice', 'creditNotes']);
    }
}
