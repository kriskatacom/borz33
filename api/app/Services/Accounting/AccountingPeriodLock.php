<?php
declare(strict_types=1);
namespace App\Services\Accounting;

use App\Exceptions\ValidationException;
use App\Models\AccountingPeriodClosure;

final class AccountingPeriodLock
{
    public function assertUnlocked(string|\DateTimeInterface|null $date): void
    {
        if ($date === null) return;
        $period = $date instanceof \DateTimeInterface ? $date->format('Y-m') : substr((string) $date, 0, 7);
        if (preg_match('/^\d{4}-\d{2}$/', $period) !== 1) return;
        if (AccountingPeriodClosure::query()->where('period', $period)->where('status', 'closed')->exists()) {
            throw new ValidationException(['period' => ["Счетоводният период {$period} е приключен и заключен."]]);
        }
    }
}
