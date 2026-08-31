<?php
declare(strict_types=1);
namespace App\Models;
final class AccountingAuditLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['action','entity_type','entity_id','before_snapshot','after_snapshot','metadata','actor_user_id','created_at'];
    protected function casts(): array { return ['before_snapshot'=>'array','after_snapshot'=>'array','metadata'=>'array','created_at'=>'datetime']; }
}
