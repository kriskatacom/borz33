<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'variant_id', 'name', 'sku', 'options', 'notes', 'qty', 'unit_price', 'total'];
    protected function casts(): array { return ['qty' => 'integer', 'unit_price' => 'decimal:2', 'total' => 'decimal:2']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
