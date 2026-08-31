<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['user_id', 'number', 'status', 'currency', 'vat_enabled', 'vat_rate', 'subtotal', 'shipping_amount', 'total', 'first_name', 'last_name', 'email', 'phone', 'delivery_method', 'econt_office_code', 'tracking_number', 'shipped_at', 'address_line', 'city', 'postal_code', 'country', 'payment_method', 'invoice_requested', 'invoice_company', 'invoice_eik', 'invoice_vat_number', 'invoice_address', 'invoice_mol', 'notes'];

    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'shipping_amount' => 'decimal:2', 'total' => 'decimal:2', 'vat_enabled' => 'boolean', 'vat_rate' => 'decimal:2', 'invoice_requested' => 'boolean', 'shipped_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function accountingTransactions(): HasMany { return $this->hasMany(AccountingTransaction::class); }
    public function econtReconciliation(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(EcontReconciliation::class); }
}
