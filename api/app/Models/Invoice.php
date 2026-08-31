<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['order_id', 'parent_invoice_id', 'type', 'number', 'status', 'issue_date', 'tax_event_date', 'currency', 'seller_snapshot', 'buyer_snapshot', 'payment_snapshot', 'items_snapshot', 'subtotal_net', 'discount_net', 'shipping_net', 'tax_amount', 'total_gross', 'reason', 'pdf_path', 'issued_at', 'cancelled_at', 'created_by'];

    protected function casts(): array
    {
        return ['seller_snapshot' => 'array', 'buyer_snapshot' => 'array', 'payment_snapshot' => 'array', 'items_snapshot' => 'array', 'subtotal_net' => 'decimal:2', 'discount_net' => 'decimal:2', 'shipping_net' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_gross' => 'decimal:2', 'issue_date' => 'date', 'tax_event_date' => 'date', 'issued_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function parentInvoice(): BelongsTo { return $this->belongsTo(self::class, 'parent_invoice_id'); }
    public function creditNotes(): HasMany { return $this->hasMany(self::class, 'parent_invoice_id'); }
}
