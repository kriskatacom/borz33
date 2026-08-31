<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class EcontReconciliation extends Model
{
    protected $fillable = ['order_id','shipment_status','tracking_number_snapshot','cod_amount','company_received_amount','received_at','notes','updated_by'];
    protected function casts(): array { return ['cod_amount'=>'decimal:2','company_received_amount'=>'decimal:2','received_at'=>'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
