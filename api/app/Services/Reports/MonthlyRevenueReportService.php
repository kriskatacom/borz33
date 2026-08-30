<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Core\Auth;
use App\Exceptions\ValidationException;
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
            $cancelledCount = (clone $base)->where('status', 'cancelled')->count();
            $recognized = (float) (clone $delivered)->sum('total');
            $productRevenue = (float) (clone $delivered)->sum('subtotal');
            $shippingRevenue = (float) (clone $delivered)->sum('shipping_amount');
            $gross = (float) (clone $base)->where('status', '!=', 'cancelled')->sum('total');
            $statusBreakdown = (clone $base)->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total), 0) as total')->groupBy('status')->get()->mapWithKeys(static fn ($row) => [(string) $row->status => ['count' => (int) $row->count, 'total' => number_format((float) $row->total, 2, '.', '')]])->all();
            $itemsQuery = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.currency', 'EUR')->where('orders.status', 'delivered')->whereBetween('orders.created_at', [$from, $to]);
            $itemsSold = (int) (clone $itemsQuery)->sum('order_items.qty');
            $topProducts = (clone $itemsQuery)->selectRaw('order_items.name, order_items.sku, SUM(order_items.qty) as qty, SUM(order_items.total) as revenue')->groupBy('order_items.name', 'order_items.sku')->orderByDesc('qty')->limit(10)->get()->map(static fn ($row) => ['name' => (string) $row->name, 'sku' => $row->sku, 'qty' => (int) $row->qty, 'revenue' => number_format((float) $row->revenue, 2, '.', '')])->all();

            $report = MonthlyRevenueReport::query()->updateOrCreate(['year' => $year, 'month' => $month, 'currency' => 'EUR'], [
                'period_start' => $startLocal->format('Y-m-d'), 'period_end' => $endLocal->format('Y-m-d'), 'orders_count' => $ordersCount,
                'delivered_orders_count' => $deliveredCount, 'cancelled_orders_count' => $cancelledCount, 'items_sold' => $itemsSold,
                'gross_turnover' => $gross, 'recognized_revenue' => $recognized, 'product_revenue' => $productRevenue, 'shipping_revenue' => $shippingRevenue,
                'average_order_value' => $deliveredCount > 0 ? round($recognized / $deliveredCount, 2) : 0,
                'status_breakdown' => $statusBreakdown, 'top_products' => $topProducts, 'generated_by' => Auth::user()?->id, 'generated_at' => CarbonImmutable::now('UTC'),
            ]);
            return $report->load('generator');
        });
    }
}
