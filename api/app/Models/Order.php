<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['user_id', 'number', 'status', 'currency', 'subtotal', 'shipping_amount', 'total', 'first_name', 'last_name', 'email', 'phone', 'delivery_method', 'econt_office_code', 'tracking_number', 'shipped_at', 'address_line', 'city', 'postal_code', 'country', 'payment_method', 'notes'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'shipping_amount' => 'decimal:2', 'total' => 'decimal:2', 'shipped_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
