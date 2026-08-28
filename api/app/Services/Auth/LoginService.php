<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Request;
use App\Exceptions\AuthException;
use App\Models\User;

class LoginService
{
    public function __construct(
        private readonly PasswordHasher $passwordHasher = new PasswordHasher(),
        private readonly LoginAttemptService $loginAttemptService = new LoginAttemptService(),
        private readonly DeviceService $deviceService = new DeviceService(),
        private readonly DeviceLoginService $deviceLoginService = new DeviceLoginService(),
        private readonly TokenService $tokenService = new TokenService()
    ) {
    }

    public function login(array $data): AuthResult
    {
        $email = strtolower(trim((string) $data['email']));
        $ip = Request::ip();
        $deviceUuid = (string) $data['device_uuid'];
        $deviceName = isset($data['device_name']) ? (string) $data['device_name'] : null;

        $this->loginAttemptService->assertNotLocked($email, $ip);

        $user = User::query()->where('email', $email)->first();
        $hash = $user?->password ?: PasswordHasher::DUMMY_HASH;
        $passwordValid = $this->passwordHasher->verify((string) $data['password'], $hash);

        if ($user === null || !$passwordValid) {
            $this->loginAttemptService->record($email, $ip, false);
            throw new AuthException('Невалиден имейл или парола.');
        }

        if (!$user->isActive()) {
            $this->loginAttemptService->record($email, $ip, false);
            throw new AuthException('Профилът е деактивиран.');
        }

        if (!$user->hasVerifiedEmail()) {
            throw new AuthException('Потвърдете имейла си преди вход.');
        }

        $this->loginAttemptService->record($email, $ip, true);

        $trusted = $this->deviceService->findTrusted($user, $deviceUuid);

        if ($trusted !== null) {
            return $this->complete($user, $trusted);
        }

        if (!$this->deviceService->hasTrustedDevice($user)) {
            $device = $this->deviceService->trust($user, $deviceUuid, $deviceName);

            return $this->complete($user, $device);
        }

        $this->deviceLoginService->challenge($user, $deviceUuid, $deviceName);

        return new AuthResult($user, true);
    }

    public function verifyDevice(array $data): AuthResult
    {
        $email = strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || !$user->isActive() || !$user->hasVerifiedEmail()) {
            throw new AuthException('Невалиден код за устройство.');
        }

        $this->deviceLoginService->verify($user, (string) $data['device_uuid'], (string) $data['code']);
        $device = $this->deviceService->trust(
            $user,
            (string) $data['device_uuid'],
            isset($data['device_name']) ? (string) $data['device_name'] : null
        );

        return $this->complete($user, $device);
    }

    public function resendDeviceCode(array $data): void
    {
        $email = strtolower(trim((string) $data['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || !$user->isActive() || !$user->hasVerifiedEmail()) {
            return;
        }

        if ($this->deviceService->findTrusted($user, (string) $data['device_uuid']) !== null) {
            return;
        }

        $this->deviceLoginService->resend(
            $user,
            (string) $data['device_uuid'],
            isset($data['device_name']) ? (string) $data['device_name'] : null
        );
    }

    private function complete(User $user, \App\Models\UserDevice $device): AuthResult
    {
        $this->deviceService->touch($device);
        $user->recordLogin(Request::ip());
        $issued = $this->tokenService->issue($user, $device);

        return new AuthResult(
            $user->fresh() ?? $user,
            false,
            $issued['token'],
            $issued['expires_at']
        );
    }
}
