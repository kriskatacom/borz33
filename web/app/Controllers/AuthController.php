<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\DeviceService;
use App\Services\Auth\LoginService;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\RegisterService;
use App\Services\Auth\TokenService;
use App\Validation\DeviceLoginResendValidator;
use App\Validation\DeviceLoginValidator;
use App\Validation\ForgotPasswordValidator;
use App\Validation\LoginValidator;
use App\Validation\RegisterValidator;
use App\Validation\ResendVerificationValidator;
use App\Validation\ResetPasswordValidator;
use App\Validation\VerifyEmailValidator;
use Store\Core\StoreAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginService $login = new LoginService(),
        private readonly LoginValidator $loginValidator = new LoginValidator(),
        private readonly DeviceLoginValidator $deviceValidator = new DeviceLoginValidator(),
        private readonly DeviceLoginResendValidator $resendValidator = new DeviceLoginResendValidator(),
        private readonly ForgotPasswordValidator $forgotPasswordValidator = new ForgotPasswordValidator(),
        private readonly ResetPasswordValidator $resetPasswordValidator = new ResetPasswordValidator(),
        private readonly PasswordResetService $passwordReset = new PasswordResetService(),
        private readonly RegisterService $register = new RegisterService(),
        private readonly RegisterValidator $registerValidator = new RegisterValidator(),
        private readonly EmailVerificationService $emailVerification = new EmailVerificationService(),
        private readonly VerifyEmailValidator $verifyEmailValidator = new VerifyEmailValidator(),
        private readonly ResendVerificationValidator $resendVerificationValidator = new ResendVerificationValidator(),
        private readonly DeviceService $devices = new DeviceService(),
        private readonly TokenService $tokens = new TokenService()
    ) {
    }

    public function showLogin(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        $this->authPage();
    }

    public function showForgotPassword(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        $this->forgotPasswordPage();
    }

    public function forgotPassword(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        try {
            $this->assertCsrf();
            $payload = $this->forgotPasswordValidator->validate([
                'email' => Request::input('email'),
            ]);
            $this->passwordReset->sendResetLink((string) $payload['email']);
        } catch (ValidationException $exception) {
            $this->forgotPasswordPage([
                'email' => (string) Request::input('email', ''),
                'errors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        } catch (AuthException $exception) {
            $this->forgotPasswordPage([
                'email' => (string) Request::input('email', ''),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        $this->forgotPasswordPage([
            'email' => (string) Request::input('email', ''),
            'message' => 'Ако този имейл принадлежи на активен профил, изпратихме линк за нова парола.',
            'isError' => false,
        ]);
    }

    public function showResetPassword(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        $this->resetPasswordPage([
            'email' => (string) Request::query('email', ''),
            'token' => (string) Request::query('token', ''),
        ]);
    }

    public function resetPassword(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        $email = (string) Request::input('email', '');
        $token = (string) Request::input('token', '');

        try {
            $this->assertCsrf();
            $payload = $this->resetPasswordValidator->validate(Request::input());
            $this->passwordReset->reset($payload);
        } catch (ValidationException $exception) {
            $this->resetPasswordPage([
                'email' => $email,
                'token' => $token,
                'errors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        } catch (AuthException $exception) {
            $this->resetPasswordPage([
                'email' => $email,
                'token' => $token,
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        $this->resetPasswordPage([
            'email' => $email,
            'token' => $token,
            'message' => 'Паролата е обновена. Вече можете да влезете в профила си.',
            'isError' => false,
            'done' => true,
        ]);
    }

    public function login(): never
    {
        if (Auth::user() !== null) {
            if ($this->wantsJson()) {
                $this->json(['success' => true, 'data' => ['redirect' => '/account']]);
            }
            $this->redirect('/account');
        }

        $step = (string) Request::input('step', 'credentials');

        try {
            $this->assertCsrf();
            if ($step === 'device') {
                $payload = $this->deviceValidator->validate($this->loginInput());
                $result = $this->login->verifyDevice($payload);
            } else {
                $payload = $this->loginValidator->validate($this->loginInput());
                $result = $this->login->login($payload);
            }
        } catch (ValidationException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), 422, $exception->errors());
            }
            $this->authPage([
                'step' => $step === 'device' ? 'device' : 'credentials',
                'email' => (string) Request::input('email', ''),
                'errors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        } catch (AuthException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), $exception->status());
            }
            $this->authPage([
                'step' => $step === 'device' ? 'device' : 'credentials',
                'email' => (string) Request::input('email', ''),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        if ($result->requiresDeviceVerification) {
            if ($this->wantsJson()) {
                $this->json([
                    'success' => true,
                    'message' => 'Вход от ново устройство. Изпратихме код на имейла Ви.',
                    'data' => [
                        'requires_device_verification' => true,
                        'email' => (string) ($payload['email'] ?? Request::input('email', '')),
                    ],
                ]);
            }
            $this->authPage([
                'step' => 'device',
                'email' => (string) ($payload['email'] ?? Request::input('email', '')),
                'message' => 'Вход от ново устройство. Изпратихме код на имейла Ви.',
                'isError' => false,
            ]);
        }

        if ($result->token === null) {
            if ($this->wantsJson()) {
                $this->authJsonError('Входът не беше успешен.', 401);
            }
            $this->authPage([
                'email' => (string) Request::input('email', ''),
                'message' => 'Входът не беше успешен.',
                'isError' => true,
            ]);
        }

        StoreAuth::persistToken($result->token, $result->expiresAt);
        $redirect = $this->returnPath((string) Request::input('return', '')) ?: '/account';
        if ($this->wantsJson()) {
            $this->json(['success' => true, 'message' => 'Входът е успешен.', 'data' => ['redirect' => $redirect]]);
        }
        $this->redirect($redirect);
    }

    public function resendCode(): never
    {
        if (Auth::user() !== null) {
            $this->redirect('/account');
        }

        try {
            $this->assertCsrf();
            $payload = $this->resendValidator->validate($this->loginInput());
            $this->login->resendDeviceCode($payload);
        } catch (ValidationException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), 422, $exception->errors());
            }
            $this->authPage([
                'step' => 'device',
                'email' => (string) Request::input('email', ''),
                'errors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        } catch (AuthException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), $exception->status());
            }
            $this->authPage([
                'step' => 'device',
                'email' => (string) Request::input('email', ''),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        if ($this->wantsJson()) {
            $this->json(['success' => true, 'message' => 'Ако е нужен код за това устройство, изпратихме нов.']);
        }

        $this->authPage([
            'step' => 'device',
            'email' => (string) Request::input('email', ''),
            'message' => 'Ако е нужен код за това устройство, изпратихме нов.',
            'isError' => false,
        ]);
    }

    public function register(): never
    {
        if (Auth::user() !== null) {
            if ($this->wantsJson()) {
                $this->json(['success' => true, 'data' => ['redirect' => '/account/profile']]);
            }
            $this->redirect('/account');
        }

        try {
            $this->assertCsrf();
            $payload = $this->registerValidator->validate($this->registerInput());
            $user = $this->register->register($payload);
        } catch (ValidationException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), 422, $exception->errors());
            }
            $this->authPage([
                'register' => $this->registerFields(),
                'registerErrors' => $exception->errors(),
                'registerMessage' => $exception->getMessage(),
                'registerIsError' => true,
            ]);
        } catch (AuthException $exception) {
            if ($this->wantsJson()) {
                $this->authJsonError($exception->getMessage(), $exception->status());
            }
            $this->authPage([
                'register' => $this->registerFields(),
                'registerMessage' => $exception->getMessage(),
                'registerIsError' => true,
            ]);
        }

        $device = $this->devices->findTrusted($user, StoreAuth::deviceUuid())
            ?? $this->devices->trust($user, StoreAuth::deviceUuid(), StoreAuth::deviceName());
        $user->recordLogin(Request::ip());
        $issued = $this->tokens->issue($user, $device);

        StoreAuth::persistToken($issued['token'], $issued['expires_at']);
        if ($this->wantsJson()) {
            $this->json([
                'success' => true,
                'message' => 'Профилът Ви е създаден.',
                'data' => ['redirect' => '/account/profile'],
            ]);
        }
        $this->redirect('/account/profile');
    }

    public function verifyEmail(): never
    {
        $authenticatedUser = Auth::user();

        $this->assertCsrf();

        try {
            $payload = $this->verifyEmailValidator->validate([
                'email' => $authenticatedUser?->email ?? Request::input('email'),
                'code' => Request::input('code'),
            ]);
            $this->emailVerification->verify($payload['email'], $payload['code']);
        } catch (ValidationException $exception) {
            if ($authenticatedUser !== null) {
                StoreAuth::setFlash($exception->getMessage(), true);
                $this->redirect('/account/profile');
            }

            $this->authPage([
                'showVerify' => true,
                'register' => $this->registerFields(),
                'registerErrors' => $exception->errors(),
                'registerMessage' => $exception->getMessage(),
                'registerIsError' => true,
            ]);
        } catch (AuthException $exception) {
            if ($authenticatedUser !== null) {
                StoreAuth::setFlash($exception->getMessage(), true);
                $this->redirect('/account/profile');
            }

            $this->authPage([
                'showVerify' => true,
                'register' => $this->registerFields(),
                'registerMessage' => $exception->getMessage(),
                'registerIsError' => true,
            ]);
        }

        if ($authenticatedUser !== null) {
            StoreAuth::setFlash('Имейл адресът Ви е потвърден.');
            $this->redirect('/account/profile');
        }

        $this->authPage([
            'email' => (string) Request::input('email', ''),
            'message' => 'Имейлът е потвърден. Вече можете да влезете.',
            'isError' => false,
            'register' => ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''],
        ]);
    }

    public function resendVerification(): never
    {
        $authenticatedUser = Auth::user();

        $this->assertCsrf();

        try {
            $payload = $this->resendVerificationValidator->validate([
                'email' => $authenticatedUser?->email ?? Request::input('email'),
            ]);
            $this->emailVerification->resend($payload['email']);
        } catch (ValidationException $exception) {
            if ($authenticatedUser !== null) {
                StoreAuth::setFlash($exception->getMessage(), true);
                $this->redirect('/account/profile');
            }

            $this->authPage([
                'showVerify' => true,
                'register' => $this->registerFields(),
                'registerErrors' => $exception->errors(),
                'registerMessage' => $exception->getMessage(),
                'registerIsError' => true,
            ]);
        }

        if ($authenticatedUser !== null) {
            StoreAuth::setFlash('Изпратихме нов код за потвърждение.');
            $this->redirect('/account/profile');
        }

        $this->authPage([
            'showVerify' => true,
            'register' => $this->registerFields(),
            'registerMessage' => 'Ако имейлът очаква потвърждение, изпратихме нов код.',
            'registerIsError' => false,
        ]);
    }

    public function logout(): never
    {
        $this->assertCsrf();
        $this->tokens->revoke(Auth::token());
        Auth::set(null);
        StoreAuth::clearToken();
        $this->redirect('/');
    }

    /** @param array<string, mixed> $extra */
    private function authPage(array $extra = []): never
    {
        $step = (string) ($extra['step'] ?? 'credentials');
        $title = $step === 'device' ? 'Потвърдете устройството · Borz33' : 'Вход и регистрация · Borz33';

        $this->view('login', [
            'title' => $title,
            'step' => $step,
            'email' => $extra['email'] ?? '',
            'errors' => $extra['errors'] ?? [],
            'message' => $extra['message'] ?? null,
            'isError' => $extra['isError'] ?? false,
            'register' => $extra['register'] ?? ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => ''],
            'registerErrors' => $extra['registerErrors'] ?? [],
            'registerMessage' => $extra['registerMessage'] ?? null,
            'registerIsError' => $extra['registerIsError'] ?? false,
            'showVerify' => (bool) ($extra['showVerify'] ?? false),
            'returnTo' => $this->returnPath((string) ($extra['returnTo'] ?? Request::input('return', Request::query('return', '')))),
            'deviceUuid' => StoreAuth::deviceUuid(),
            'deviceName' => StoreAuth::deviceName(),
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function forgotPasswordPage(array $extra = []): never
    {
        $this->view('forgot-password', [
            'title' => 'Забравена парола · Borz33',
            'email' => $extra['email'] ?? '',
            'errors' => $extra['errors'] ?? [],
            'message' => $extra['message'] ?? null,
            'isError' => $extra['isError'] ?? false,
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function resetPasswordPage(array $extra = []): never
    {
        $this->view('reset-password', [
            'title' => 'Нова парола · Borz33',
            'email' => $extra['email'] ?? '',
            'token' => $extra['token'] ?? '',
            'errors' => $extra['errors'] ?? [],
            'message' => $extra['message'] ?? null,
            'isError' => $extra['isError'] ?? false,
            'done' => $extra['done'] ?? false,
        ]);
    }

    private function returnPath(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return '';
        }

        return $path;
    }

    /** @param array<string, mixed> $errors */
    private function authJsonError(string $message, int $status, array $errors = []): never
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }
        $this->json($payload, $status);
    }

    /** @return array<string, mixed> */
    private function loginInput(): array
    {
        return [
            'email' => Request::input('email'),
            'password' => Request::input('password'),
            'code' => Request::input('code'),
            'device_uuid' => StoreAuth::deviceUuid(),
            'device_name' => StoreAuth::deviceName(),
        ];
    }

    /** @return array<string, mixed> */
    private function registerInput(): array
    {
        return [
            'first_name' => Request::input('first_name'),
            'last_name' => Request::input('last_name'),
            'email' => Request::input('email'),
            'password' => Request::input('password'),
            'password_confirmation' => Request::input('password_confirmation'),
            'phone' => Request::input('phone'),
            'device_uuid' => StoreAuth::deviceUuid(),
            'device_name' => StoreAuth::deviceName(),
        ];
    }

    /** @return array<string, string> */
    private function registerFields(): array
    {
        return [
            'first_name' => (string) Request::input('first_name', ''),
            'last_name' => (string) Request::input('last_name', ''),
            'email' => (string) Request::input('email', ''),
            'phone' => (string) Request::input('phone', ''),
        ];
    }
}
