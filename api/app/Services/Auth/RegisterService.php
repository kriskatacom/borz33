<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Carbon;

class RegisterService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
        private readonly AdminBootstrapService $adminBootstrapService = new AdminBootstrapService()
    ) {
    }

    public function register(array $data): User
    {
        return Capsule::connection()->transaction(function () use ($data): User {
            $this->adminBootstrapService->ensureExists();

            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $this->passwordHasher->hash((string) $data['password']),
                'phone' => $data['phone'] ?? null,
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
            ]);

            EmailVerificationToken::query()->updateOrCreate(
                ['email' => $user->email],
                [
                    'token' => hash('sha256', bin2hex(random_bytes(32))),
                    'created_at' => Carbon::now(),
                ]
            );

            return $user;
        });
    }
}
