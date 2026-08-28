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
        $password = (string) (getenv('ADMIN_PASSWORD') ?: '');

        if ($password === '') {
            throw new RuntimeException('ADMIN_PASSWORD не е конфигурирана.');
        }

        $email = self::configuredEmail();
        $admin = User::withTrashed()->where('email', $email)->first() ?? new User();
        $isNew = !$admin->exists;

        if ($admin->trashed()) {
            $admin->restore();
        }

        if ($isNew) {
            $admin->forceFill([
                'first_name' => (string) (getenv('ADMIN_FIRST_NAME') ?: 'Admin'),
                'last_name' => (string) (getenv('ADMIN_LAST_NAME') ?: 'User'),
                'email' => $email,
                'phone' => null,
                'email_verified_at' => Carbon::now(),
            ]);
        }

        $needsPassword = $isNew || !$this->passwordHasher->verify($password, (string) $admin->password);

        $admin->forceFill([
            'email' => $email,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? Carbon::now(),
        ]);

        if ($needsPassword) {
            $admin->password = $this->passwordHasher->hash($password);
        }

        $admin->save();
    }
}
