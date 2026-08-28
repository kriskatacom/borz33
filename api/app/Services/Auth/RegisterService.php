<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class RegisterService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
        private readonly AdminBootstrapService $adminBootstrapService = new AdminBootstrapService(),
        private readonly EmailVerificationService $emailVerificationService = new EmailVerificationService(),
        private readonly DeviceService $deviceService = new DeviceService()
    ) {
    }

    public function register(array $data): User
    {
        $code = '';

        $user = Capsule::connection()->transaction(function () use ($data, &$code): User {
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

            $this->deviceService->trust(
                $user,
                (string) $data['device_uuid'],
                isset($data['device_name']) ? (string) $data['device_name'] : null
            );

            $code = $this->emailVerificationService->storeCode($user);

            return $user;
        });

        $this->emailVerificationService->send($user, $code);

        return $user;
    }
}
