<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Core\Auth;
use App\Exceptions\ValidationException;
use App\Models\Invoice;
use App\Models\MonthlyRevenueReport;
use App\Models\Order;
use App\Resources\MonthlyRevenueReportResource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Capsule\Manager as DB;

class MonthlyRevenueReportService
{
    public function list(): array
    {
        return MonthlyRevenueReport::query()->with('generator')->orderByDesc('year')->orderByDesc('month')->get()->map(static fn ($report) => MonthlyRevenueReportResource::toArray($report))->all();
    }

    public function generate(mixed $period): MonthlyRevenueReport
    {
        $period = is_string($period) ? trim($period) : '';
        if (preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $period, $matches) !== 1) throw new ValidationException(['period' => ['Изберете валиден месец.']]);
        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $startLocal = CarbonImmutable::create($year, $month, 1, 0, 0, 0, 'Europe/Sofia');
        $endLocal = $startLocal->endOfMonth();
        if ($startLocal->startOfMonth()->isAfter(CarbonImmutable::now('Europe/Sofia')->endOfMonth())) throw new ValidationException(['period' => ['Не може да генерирате отчет за бъдещ месец.']]);
        $from = $startLocal->utc();
        $to = $endLocal->utc();

        return DB::connection()->transaction(function () use ($year, $month, $startLocal, $endLocal, $from, $to): MonthlyRevenueReport {
            $base = Order::query()->where('currency', 'EUR')->whereBetween('created_at', [$from, $to]);
            $ordersCount = (clone $base)->count();
            $delivered = (clone $base)->where('status', 'delivered');
            $deliveredCount = (clone $delivered)->count();
            $paidOrders = (clone $base)->with(['items', 'accountingTransactions'])->get()->filter(static function (Order $order): bool {
                $paid = (float) $order->accountingTransactions->where('type', 'payment')->where('status', 'completed')->sum('amount');
                return $paid >= (float) $order->total - 0.009;
            })->values();
            $paidOrderIds = $paidOrders->pluck('id')->all();
            $paidOrdersCount = $paidOrders->count();
            $cancelledCount = (clone $base)->where('status', 'cancelled')->count();
            $gross = round((float) $paidOrders->sum('total'), 2);
            $creditNotes = Invoice::query()->where('type', 'credit_note')->whereIn('status', ['issued', 'credited'])->whereBetween('issue_date', [$startLocal->format('Y-m-d'), $endLocal->format('Y-m-d')])->get();
            $creditNotesAmount = round(abs((float) $creditNotes->sum('total_gross')), 2);
            $creditedProductAmount = 0.0;
            foreach ($creditNotes as $creditNote) foreach ($creditNote->items_snapshot as $item) $creditedProductAmount += abs((float) ($item['gross_total'] ?? 0));
            $productRevenue = round((float) $paidOrders->sum('subtotal') - $creditedProductAmount, 2);
            $recognized = round($gross - $creditNotesAmount, 2);
            $shippingRevenue = round($recognized - $productRevenue, 2);
            $statusBreakdown = (clone $base)->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total), 0) as total')->groupBy('status')->get()->mapWithKeys(static fn ($row) => [(string) $row->status => ['count' => (int) $row->count, 'total' => number_format((float) $row->total, 2, '.', '')]])->all();
            // Credit notes contain negative quantities and amounts. Apply them to the
            // matching sold items so product counts and rankings reflect net sales.
            $productTotals = [];
            $addProduct = static function (array &$totals, string $name, ?string $sku, int $qty, float $revenue): void {
                $key = $name . "\0" . ($sku ?? '');
                if (!isset($totals[$key])) $totals[$key] = ['name' => $name, 'sku' => $sku, 'qty' => 0, 'revenue' => 0.0];
                $totals[$key]['qty'] += $qty;
                $totals[$key]['revenue'] += $revenue;
            };
            foreach ($paidOrders as $order) foreach ($order->items as $item) $addProduct($productTotals, (string) $item->name, $item->sku === null ? null : (string) $item->sku, (int) $item->qty, (float) $item->total);
            foreach ($creditNotes as $creditNote) foreach ($creditNote->items_snapshot as $item) $addProduct($productTotals, (string) ($item['name'] ?? ''), isset($item['sku']) ? (string) $item['sku'] : null, (int) ($item['qty'] ?? 0), (float) ($item['gross_total'] ?? 0));
            $productTotals = array_values(array_filter($productTotals, static fn (array $product): bool => $product['qty'] > 0));
            usort($productTotals, static fn (array $left, array $right): int => $right['qty'] <=> $left['qty'] ?: $right['revenue'] <=> $left['revenue'] ?: $left['name'] <=> $right['name']);
            $itemsSold = array_sum(array_column($productTotals, 'qty'));
            $topProducts = array_map(static fn (array $product): array => ['name' => $product['name'], 'sku' => $product['sku'], 'qty' => $product['qty'], 'revenue' => number_format(round($product['revenue'], 2), 2, '.', '')], array_slice($productTotals, 0, 10));

            $report = MonthlyRevenueReport::query()->updateOrCreate(['year' => $year, 'month' => $month, 'currency' => 'EUR'], [
                'period_start' => $startLocal->format('Y-m-d'), 'period_end' => $endLocal->format('Y-m-d'), 'orders_count' => $ordersCount,
                'delivered_orders_count' => $deliveredCount, 'paid_orders_count' => $paidOrdersCount, 'cancelled_orders_count' => $cancelledCount, 'items_sold' => $itemsSold,
                'gross_turnover' => $gross, 'recognized_revenue' => $recognized, 'product_revenue' => $productRevenue, 'shipping_revenue' => $shippingRevenue,
                'average_order_value' => $paidOrdersCount > 0 ? round($recognized / $paidOrdersCount, 2) : 0,
                'credit_notes_count' => $creditNotes->count(), 'credit_notes_amount' => $creditNotesAmount,
                'status_breakdown' => $statusBreakdown, 'top_products' => $topProducts, 'generated_by' => Auth::user()?->id, 'generated_at' => CarbonImmutable::now('UTC'),
            ]);
            return $report->load('generator');
        });
    }
}
