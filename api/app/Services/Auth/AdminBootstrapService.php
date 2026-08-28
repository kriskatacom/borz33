<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

class AdminBootstrapService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher()
    ) {
    }

    public static function configuredEmail(): string
    {
        return strtolower(trim((string) (getenv('ADMIN_EMAIL') ?: 'admin@borz33.local')));
    }

    public function ensureExists(): void
    {
        if (User::withTrashed()->where('role', User::ROLE_ADMIN)->exists()) {
            return;
        }

        $password = (string) (getenv('ADMIN_PASSWORD') ?: '');

        if ($password === '') {
            throw new RuntimeException('ADMIN_PASSWORD не е конфигурирана.');
        }

        $admin = new User();
        $admin->forceFill([
            'first_name' => (string) (getenv('ADMIN_FIRST_NAME') ?: 'Admin'),
            'last_name' => (string) (getenv('ADMIN_LAST_NAME') ?: 'User'),
            'email' => self::configuredEmail(),
            'password' => $this->passwordHasher->hash($password),
            'phone' => null,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => Carbon::now(),
        ]);
        $admin->save();
    }
}
