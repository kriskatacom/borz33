<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\MonthlyRevenueReport;

class MonthlyRevenueReportResource
{
    public static function toArray(MonthlyRevenueReport $report): array
    {
        return [
            'id' => (int) $report->id, 'year' => (int) $report->year, 'month' => (int) $report->month, 'currency' => $report->currency,
            'period_start' => $report->period_start?->format('Y-m-d'), 'period_end' => $report->period_end?->format('Y-m-d'),
            'orders_count' => (int) $report->orders_count, 'delivered_orders_count' => (int) $report->delivered_orders_count, 'paid_orders_count' => (int) $report->paid_orders_count, 'cancelled_orders_count' => (int) $report->cancelled_orders_count, 'items_sold' => (int) $report->items_sold,
            'gross_turnover' => $report->gross_turnover, 'recognized_revenue' => $report->recognized_revenue, 'product_revenue' => $report->product_revenue, 'shipping_revenue' => $report->shipping_revenue, 'average_order_value' => $report->average_order_value, 'credit_notes_count' => (int) $report->credit_notes_count, 'credit_notes_amount' => $report->credit_notes_amount,
            'status_breakdown' => $report->status_breakdown ?? [], 'top_products' => $report->top_products ?? [],
            'generated_by' => $report->generator?->fullName() ?: 'Администратор', 'generated_at' => $report->generated_at?->toIso8601String(),
        ];
    }
}
