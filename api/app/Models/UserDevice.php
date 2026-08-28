<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_name',
        'user_agent',
        'ip_address',
        'is_trusted',
        'trusted_at',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'is_active' => 'boolean',
            'trusted_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
