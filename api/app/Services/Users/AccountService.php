<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Core\Auth;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Services\Auth\PasswordHasher;

class AccountService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher()
    ) {
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(User $user, array $data): User
    {
        $email = strtolower(trim((string) $data['email']));
        $emailChanged = $email !== $user->email;
        $currentPassword = (string) ($data['current_password'] ?? '');

        if ($emailChanged) {
            $this->assertCurrentPassword($user, $currentPassword, 'Въведете текущата парола, за да смените имейла.');
        }

        $phone = trim((string) ($data['phone'] ?? ''));

        $user->forceFill([
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
        ])->save();

        return $user->refresh();
    }

    /** @param array<string, mixed> $data */
    public function changePassword(User $user, array $data): void
    {
        $this->assertCurrentPassword($user, (string) $data['current_password'], 'Текущата парола е грешна.');

        $user->forceFill([
            'password' => $this->passwordHasher->hash((string) $data['password']),
        ])->save();

        $currentId = Auth::token()?->id;

        $query = $user->apiTokens();

        if ($currentId !== null) {
            $query->where('id', '!=', $currentId);
        }

        $query->delete();
    }

    private function assertCurrentPassword(User $user, string $plain, string $message): void
    {
        if ($plain === '' || !$this->passwordHasher->verify($plain, (string) $user->password)) {
            throw new ValidationException(['current_password' => [$message]]);
        }
    }
}
