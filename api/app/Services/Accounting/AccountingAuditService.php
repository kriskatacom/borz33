<?php
declare(strict_types=1);
namespace App\Services\Accounting;

use App\Core\Auth;
use App\Models\AccountingAuditLog;

final class AccountingAuditService
{
    public function write(string $action, string $entityType, ?int $entityId, ?array $before = null, ?array $after = null, array $metadata = []): void
    {
        AccountingAuditLog::query()->create([
            'action' => $action, 'entity_type' => $entityType, 'entity_id' => $entityId,
            'before_snapshot' => $before, 'after_snapshot' => $after, 'metadata' => $metadata ?: null,
            'actor_user_id' => Auth::user()?->id, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
