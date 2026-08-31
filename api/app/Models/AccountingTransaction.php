<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class AccountingTransaction extends Model
{
    protected $fillable = ['order_id','type','method','status','amount','currency','external_reference','notes','occurred_at','created_by'];
    protected function casts(): array { return ['amount'=>'decimal:2','occurred_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
