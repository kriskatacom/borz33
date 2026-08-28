<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Auth\AuthResult;
use App\Services\Auth\LoginService;
use App\Validation\DeviceLoginResendValidator;
use App\Validation\DeviceLoginValidator;
use App\Validation\LoginValidator;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginValidator $loginValidator = new LoginValidator(),
        private readonly DeviceLoginValidator $deviceLoginValidator = new DeviceLoginValidator(),
        private readonly DeviceLoginResendValidator $deviceLoginResendValidator = new DeviceLoginResendValidator(),
        private readonly LoginService $loginService = new LoginService()
    ) {
    }

    public function store(): never
    {
        $payload = $this->loginValidator->validate(Request::input());
        $this->respondLogin($this->loginService->login($payload));
    }

    public function storeAdmin(): never
    {
        $payload = $this->loginValidator->validate(Request::input());
        $this->respondLogin($this->loginService->login($payload, true));
    }

    public function verifyDevice(): never
    {
        $payload = $this->deviceLoginValidator->validate(Request::input());
        $result = $this->loginService->verifyDevice($payload);

        $this->ok($result->toArray(), 'Устройството е потвърдено. Входът е успешен.');
    }

    public function verifyDeviceAdmin(): never
    {
        $payload = $this->deviceLoginValidator->validate(Request::input());
        $result = $this->loginService->verifyDevice($payload, true);

        $this->ok($result->toArray(), 'Устройството е потвърдено. Входът е успешен.');
    }

    public function resendDeviceCode(): never
    {
        $payload = $this->deviceLoginResendValidator->validate(Request::input());
        $this->loginService->resendDeviceCode($payload);

        $this->ok([], 'Ако е нужен код за това устройство, изпратихме нов.');
    }

    public function resendDeviceCodeAdmin(): never
    {
        $payload = $this->deviceLoginResendValidator->validate(Request::input());
        $this->loginService->resendDeviceCode($payload, true);

        $this->ok([], 'Ако е нужен код за това устройство, изпратихме нов.');
    }

    private function respondLogin(AuthResult $result): never
    {
        if ($result->requiresDeviceVerification) {
            $this->ok($result->toArray(), 'Вход от ново устройство. Изпратихме код на имейла Ви.');
        }

        $this->ok($result->toArray(), 'Входът е успешен.');
    }
}
