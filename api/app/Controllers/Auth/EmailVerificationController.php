<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Controller;
use App\Core\Request;
use App\Resources\UserResource;
use App\Services\Auth\EmailVerificationService;
use App\Validation\ResendVerificationValidator;
use App\Validation\VerifyEmailValidator;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly VerifyEmailValidator $verifyEmailValidator = new VerifyEmailValidator(),
        private readonly ResendVerificationValidator $resendVerificationValidator = new ResendVerificationValidator(),
        private readonly EmailVerificationService $emailVerificationService = new EmailVerificationService()
    ) {
    }

    public function verify(): never
    {
        $payload = $this->verifyEmailValidator->validate(Request::input());
        $user = $this->emailVerificationService->verify($payload['email'], $payload['code']);

        $this->ok(UserResource::toArray($user), 'Имейлът е потвърден.');
    }

    public function resend(): never
    {
        $payload = $this->resendVerificationValidator->validate(Request::input());
        $this->emailVerificationService->resend($payload['email']);

        $this->ok([], 'Ако имейлът очаква потвърждение, изпратихме нов код.');
    }
}
