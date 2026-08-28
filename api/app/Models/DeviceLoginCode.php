<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLoginCode extends Model
{
    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_name',
        'user_agent',
        'ip_address',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
