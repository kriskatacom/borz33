<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Resources\UserResource;

final class AuthResult
{
    public function __construct(
        public readonly User $user,
        public readonly bool $requiresDeviceVerification = false,
        public readonly ?string $token = null,
        public readonly ?string $expiresAt = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'requires_device_verification' => $this->requiresDeviceVerification,
            'user' => UserResource::toArray($this->user),
        ];

        if ($this->token !== null) {
            $data['token'] = $this->token;
            $data['token_type'] = 'Bearer';
            $data['expires_at'] = $this->expiresAt;
        }

        return $data;
    }
}
