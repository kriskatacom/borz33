<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Auth\PasswordResetService;
use App\Validation\ForgotPasswordValidator;
use App\Validation\ResetPasswordValidator;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly ForgotPasswordValidator $forgotValidator = new ForgotPasswordValidator(),
        private readonly ResetPasswordValidator $resetValidator = new ResetPasswordValidator(),
        private readonly PasswordResetService $passwordResetService = new PasswordResetService()
    ) {
    }

    public function forgotAdmin(): never
    {
        $payload = $this->forgotValidator->validate(Request::input());
        $this->passwordResetService->sendResetLink((string) $payload['email'], true);

        $this->ok([], 'Ако този имейл принадлежи на администраторски профил, изпратихме линк за нова парола.');
    }

    public function forgot(): never
    {
        $payload = $this->forgotValidator->validate(Request::input());
        $this->passwordResetService->sendResetLink((string) $payload['email']);

        $this->ok([], 'Ако този имейл принадлежи на активен профил, изпратихме линк за нова парола.');
    }

    public function resetAdmin(): never
    {
        $payload = $this->resetValidator->validate(Request::input());
        $this->passwordResetService->reset($payload, true);

        $this->ok([], 'Паролата е обновена. Влезте с новата парола.');
    }

    public function reset(): never
    {
        $payload = $this->resetValidator->validate(Request::input());
        $this->passwordResetService->reset($payload);

        $this->ok([], 'Паролата е обновена. Вече можете да влезете в профила си.');
    }
}
