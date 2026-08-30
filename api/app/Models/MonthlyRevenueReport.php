<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyRevenueReport extends Model
{
    protected $fillable = ['year', 'month', 'currency', 'period_start', 'period_end', 'orders_count', 'delivered_orders_count', 'cancelled_orders_count', 'items_sold', 'gross_turnover', 'recognized_revenue', 'product_revenue', 'shipping_revenue', 'average_order_value', 'status_breakdown', 'top_products', 'generated_by', 'generated_at'];
    protected function casts(): array { return ['period_start' => 'date', 'period_end' => 'date', 'generated_at' => 'datetime', 'status_breakdown' => 'array', 'top_products' => 'array', 'gross_turnover' => 'decimal:2', 'recognized_revenue' => 'decimal:2', 'product_revenue' => 'decimal:2', 'shipping_revenue' => 'decimal:2', 'average_order_value' => 'decimal:2']; }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
}
