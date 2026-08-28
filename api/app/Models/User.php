<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class User extends Model
{
    use SoftDeletes;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => strtolower(trim($value)),
        );
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();
    }

    public function recordLogin(?string $ip): bool
    {
        return $this->forceFill([
            'last_login_at' => Carbon::now(),
            'last_login_ip' => $ip,
        ])->save();
    }

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function deviceLoginCodes(): HasMany
    {
        return $this->hasMany(DeviceLoginCode::class);
    }
}
