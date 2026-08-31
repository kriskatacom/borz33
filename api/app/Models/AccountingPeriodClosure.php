<?php
declare(strict_types=1);
namespace App\Models;
final class AccountingPeriodClosure extends Model
{
    protected $fillable = ['period','status','summary_snapshot','package_path','closed_at','closed_by'];
    protected function casts(): array { return ['summary_snapshot'=>'array','closed_at'=>'datetime']; }
}
