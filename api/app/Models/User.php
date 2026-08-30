<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class User extends Model
{
    use SoftDeletes;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_ADMIN = 'admin';

    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const THEME_SYSTEM = 'system';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'avatar_media_id',
        'role',
        'is_active',
        'theme',
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
            'theme' => 'string',
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

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(UserAddress::class)
            ->where('type', UserAddress::TYPE_BILLING)
            ->orderByDesc('is_default')
            ->orderByDesc('id');
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'user_favorite_products')
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc');
    }

    public function recentlyViewedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'user_recently_viewed_products')
            ->withPivot('viewed_at')
            ->orderByPivot('viewed_at', 'desc');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderByDesc('created_at')->orderByDesc('id');
    }
}
