<?php

declare(strict_types=1);

namespace App\Models;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'ip_address',
        'successful',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
