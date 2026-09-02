<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SiteSetting;
use App\Models\User;

class DashboardAdminService
{
    /** @return array<string, int> */
    public function summary(): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $ordersToday = Order::query()->whereDate('created_at', $today);
        $ordersMonth = Order::query()->whereBetween('created_at', [$monthStart . ' 00:00:00', date('Y-m-d') . ' 23:59:59']);
        $lowStockThreshold = (int) (SiteSetting::query()->value('low_stock_threshold') ?? 5);

        return [
            'products_active' => Product::query()->where('is_active', true)->count(),
            'low_stock' => $lowStockThreshold > 0 ? ProductVariant::query()
                ->where('is_active', true)
                ->where('stock', '<', $lowStockThreshold)
                ->whereHas('product', static function ($query): void {
                    $query->where('is_active', true);
                })
                ->count() : 0,
            'banners_active' => Banner::query()->where('is_active', true)->count(),
            'customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'categories_active' => Category::query()->where('is_active', true)->count(),
            'pages_active' => Page::query()->where('is_active', true)->count(),
            'media' => MediaFile::query()->count(),
            'orders_today' => (clone $ordersToday)->count(),
            'orders_month' => (clone $ordersMonth)->count(),
            'revenue_month' => (float) (clone $ordersMonth)->where('status', '!=', 'cancelled')->sum('total'),
            'pending_orders' => Order::query()->whereIn('status', ['pending', 'confirmed'])->count(),
            'invoices_month' => Invoice::query()->where('type', 'invoice')->whereBetween('issue_date', [$monthStart, date('Y-m-d')])->count(),
            'recent_orders' => Order::query()->latest('created_at')->limit(5)->get(['id', 'number', 'first_name', 'last_name', 'status', 'total', 'currency', 'created_at'])->map(static fn (Order $order): array => [
                'id' => (int) $order->id,
                'number' => $order->number,
                'customer' => trim($order->first_name . ' ' . $order->last_name),
                'status' => $order->status,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->all(),
        ];
    }
}
