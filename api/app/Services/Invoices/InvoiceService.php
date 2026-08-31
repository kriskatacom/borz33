<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\Accounting\AccountingAuditService;
use App\Services\Accounting\AccountingPeriodLock;
use Illuminate\Database\Capsule\Manager as Capsule;

final class InvoiceService
{
    public const STATUSES = ['draft', 'issued', 'cancelled', 'credited'];

    public function __construct(private readonly InvoicePdfService $pdf = new InvoicePdfService(), private readonly AccountingPeriodLock $periodLock = new AccountingPeriodLock(), private readonly AccountingAuditService $audit = new AccountingAuditService()) {}

    public function createForOrder(Order $order, bool $issue = true, ?int $createdBy = null): Invoice
    {
        if ($issue) $this->periodLock->assertUnlocked(date('Y-m-d'));
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

        if (!$issue) return $invoice;
        $invoice = $this->persistPdf($invoice);
        $this->audit->write('invoice.issued', 'invoice', (int) $invoice->id, null, $invoice->toArray());
        return $invoice;
    }

    public function issue(Invoice $invoice): Invoice
    {
        $this->periodLock->assertUnlocked(date('Y-m-d'));
        if ($invoice->status !== 'draft') throw new ValidationException(['status' => ['Само чернова може да бъде издадена.']]);
        $invoice = Capsule::connection()->transaction(fn (): Invoice => $this->issueInsideTransaction($invoice));
        $invoice = $this->persistPdf($invoice);
        $this->audit->write('invoice.issued', 'invoice', (int) $invoice->id, null, $invoice->toArray());
        return $invoice;
    }

    /** @param list<array{index: int, qty: int}> $selections */
    public function creditItems(Invoice $invoice, string $reason, array $selections, bool $refundShipping, ?int $createdBy = null): Invoice
    {
        $this->periodLock->assertUnlocked(date('Y-m-d'));
        $credit = Capsule::connection()->transaction(function () use ($invoice, $reason, $selections, $refundShipping, $createdBy): Invoice {
            $lockedInvoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
            if ($lockedInvoice === null) throw new AuthException('Фактурата не е намерена.', 404);
            return $this->creditItemsLocked($lockedInvoice, $reason, $selections, $refundShipping, $createdBy);
        });
        $credit = $this->persistPdf($credit);
        $this->audit->write('credit_note.issued', 'invoice', (int) $credit->id, null, $credit->toArray(), ['parent_invoice_id' => $invoice->id]);
        return $credit;
    }

    /** @param list<array{index: int, qty: int}> $selections */
    private function creditItemsLocked(Invoice $invoice, string $reason, array $selections, bool $refundShipping, ?int $createdBy): Invoice
    {
        if ($invoice->type !== 'invoice' || $invoice->status !== 'issued') throw new ValidationException(['invoice' => ['Кредитно известие може да се издаде само към некредитирана издадена фактура.']]);
        $reason = trim($reason);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) throw new ValidationException(['reason' => ['Опишете точно основанието за избраните позиции.']]);

        $activeCredits = $invoice->creditNotes()->where('status', '!=', 'cancelled')->get();
        $creditedQuantities = [];
        $shippingAlreadyCredited = false;
        foreach ($activeCredits as $activeCredit) {
            $shippingAlreadyCredited = $shippingAlreadyCredited || abs((float) $activeCredit->shipping_net) >= 0.01;
            foreach ($activeCredit->items_snapshot as $creditedItem) {
                if (!array_key_exists('source_index', $creditedItem)) throw new ValidationException(['items' => ['Съществува по-старо кредитно известие без връзка към конкретни позиции. Не може безопасно да се издаде ново частично известие.']]);
                $sourceIndex = (int) $creditedItem['source_index'];
                $creditedQuantities[$sourceIndex] = ($creditedQuantities[$sourceIndex] ?? 0) + abs((int) ($creditedItem['qty'] ?? 0));
            }
        }

        $selectedQuantities = [];
        $items = [];
        foreach ($selections as $selection) {
            $index = (int) ($selection['index'] ?? -1);
            $qty = (int) ($selection['qty'] ?? 0);
            $source = $invoice->items_snapshot[$index] ?? null;
            if (isset($selectedQuantities[$index])) throw new ValidationException(['items' => ['Една позиция не може да бъде добавена два пъти.']]);
            $remainingQty = is_array($source) ? max(0, (int) ($source['qty'] ?? 0) - (int) ($creditedQuantities[$index] ?? 0)) : 0;
            if (!is_array($source) || $qty < 1 || $qty > $remainingQty) throw new ValidationException(['items' => ['Избраното количество надвишава оставащото за кредитиране.']]);
            $selectedQuantities[$index] = $qty;
            $rate = max(0.0, (float) ($source['tax_rate'] ?? 0));
            $divisor = 1 + $rate / 100;
            $gross = round((float) ($source['unit_gross'] ?? 0) * $qty, 2); $net = round($gross / $divisor, 2); $tax = round($gross - $net, 2);
            $items[] = ['source_index' => $index, 'name' => (string) $source['name'], 'sku' => (string) ($source['sku'] ?? ''), 'qty' => -$qty, 'unit_gross' => -round((float) ($source['unit_gross'] ?? 0), 2), 'net_total' => -$net, 'tax_rate' => $rate, 'tax' => -$tax, 'gross_total' => -$gross];
        }
        if ($items === [] && !$refundShipping) throw new ValidationException(['items' => ['Изберете продукт или възстановяване на доставка.']]);
        if ($refundShipping && $shippingAlreadyCredited) throw new ValidationException(['refund_shipping' => ['Доставката вече е кредитирана.']]);

        $shippingRate = max(0.0, (float) ($invoice->seller_snapshot['vat_rate'] ?? 0));
        $shippingNet = $refundShipping ? -round((float) $invoice->shipping_net, 2) : 0.0;
        $shippingTax = $refundShipping ? -round(abs($shippingNet) * $shippingRate / 100, 2) : 0.0;
        $subtotalNet = round(array_sum(array_column($items, 'net_total')), 2);
        $taxAmount = round(array_sum(array_column($items, 'tax')) + $shippingTax, 2);
        $totalGross = round($subtotalNet + $shippingNet + $taxAmount, 2);

        $allItemsCredited = true;
        foreach ($invoice->items_snapshot as $index => $source) {
            $totalCreditedQty = (int) ($creditedQuantities[$index] ?? 0) + (int) ($selectedQuantities[$index] ?? 0);
            if ($totalCreditedQty < (int) ($source['qty'] ?? 0)) { $allItemsCredited = false; break; }
        }
        $shippingFullyCredited = abs((float) $invoice->shipping_net) < 0.01 || $shippingAlreadyCredited || $refundShipping;

        $credit = Capsule::connection()->transaction(function () use ($invoice, $reason, $items, $subtotalNet, $shippingNet, $taxAmount, $totalGross, $createdBy, $allItemsCredited, $shippingFullyCredited): Invoice {
            $credit = Invoice::query()->create(['order_id' => $invoice->order_id, 'parent_invoice_id' => $invoice->id, 'type' => 'credit_note', 'status' => 'draft', 'currency' => $invoice->currency, 'seller_snapshot' => $invoice->seller_snapshot, 'buyer_snapshot' => $invoice->buyer_snapshot, 'payment_snapshot' => $invoice->payment_snapshot, 'items_snapshot' => $items, 'subtotal_net' => $subtotalNet, 'discount_net' => 0, 'shipping_net' => $shippingNet, 'tax_amount' => $taxAmount, 'total_gross' => $totalGross, 'reason' => $reason, 'created_by' => $createdBy]);
            $credit = $this->issueInsideTransaction($credit);
            if ($allItemsCredited && $shippingFullyCredited) { $invoice->status = 'credited'; $invoice->save(); }
            return $credit;
        });
        return $credit;
    }

    public function cancel(Invoice $invoice, string $reason): Invoice
    {
        $this->periodLock->assertUnlocked($invoice->issue_date ?? date('Y-m-d'));
        if (!in_array($invoice->status, ['draft', 'issued'], true)) throw new ValidationException(['status' => ['Документът не може да бъде анулиран в текущия статус.']]);
        $invoice->status = 'cancelled'; $invoice->reason = trim($reason) ?: 'Анулиран от администратор'; $invoice->cancelled_at = new \DateTimeImmutable(); $invoice->save();
        $fresh = $invoice->fresh(['order', 'parentInvoice', 'creditNotes']);
        $this->audit->write('invoice.cancelled', 'invoice', (int) $invoice->id, null, $fresh->toArray());
        return $fresh;
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
