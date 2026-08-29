<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Services\Users\AccountService;
use App\Validation\ChangeAccountPasswordValidator;
use App\Validation\UpdateAccountProfileValidator;
use Store\Core\StoreAuth;
use Store\Core\View;

class AccountController extends Controller
{
    public const SECTIONS = [
        'dashboard' => 'Табло',
        'details' => 'Данни на акаунта',
        'password' => 'Парола',
        'orders' => 'Поръчки',
        'addresses' => 'Адреси',
        'appearance' => 'Изглед',
    ];

    public function __construct(
        private readonly AccountService $accounts = new AccountService(),
        private readonly UpdateAccountProfileValidator $profileValidator = new UpdateAccountProfileValidator(),
        private readonly ChangeAccountPasswordValidator $passwordValidator = new ChangeAccountPasswordValidator()
    ) {
    }

    public function show(?string $section = null): never
    {
        $this->renderSection($section ?? 'dashboard');
    }

    public function updateProfile(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();

        $input = [
            'first_name' => Request::input('first_name'),
            'last_name' => Request::input('last_name'),
            'email' => Request::input('email'),
            'phone' => Request::input('phone'),
            'current_password' => Request::input('current_password'),
        ];

        try {
            $payload = $this->profileValidator->validate($input, (int) $user->id);
            $this->accounts->updateProfile($user, $payload);
        } catch (ValidationException $exception) {
            $this->renderSection('details', [
                'profile' => [
                    'first_name' => (string) ($input['first_name'] ?? ''),
                    'last_name' => (string) ($input['last_name'] ?? ''),
                    'email' => (string) ($input['email'] ?? ''),
                    'phone' => (string) ($input['phone'] ?? ''),
                ],
                'profileErrors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        StoreAuth::setFlash('Данните на акаунта са запазени.');
        $this->redirect('/account/details');
    }

    public function updatePassword(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();

        $input = [
            'current_password' => Request::input('current_password'),
            'password' => Request::input('password'),
            'password_confirmation' => Request::input('password_confirmation'),
        ];

        try {
            $payload = $this->passwordValidator->validate($input);
            $this->accounts->changePassword($user, $payload);
        } catch (ValidationException $exception) {
            $this->renderSection('password', [
                'passwordErrors' => $exception->errors(),
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        StoreAuth::setFlash('Паролата е сменена. Другите сесии са прекратени.');
        $this->redirect('/account/password');
    }

    public function updateTheme(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();

        $theme = (string) Request::input('theme', User::THEME_SYSTEM);
        $allowed = [User::THEME_LIGHT, User::THEME_DARK, User::THEME_SYSTEM];

        if (!in_array($theme, $allowed, true)) {
            $this->renderSection('appearance', [
                'message' => 'Невалидна тема.',
                'isError' => true,
            ]);
        }

        $user->forceFill(['theme' => $theme])->save();
        StoreAuth::setFlash('Изгледът е запазен.');
        $this->redirect('/account/appearance');
    }

    /** @param array<string, mixed> $extra */
    private function renderSection(string $section, array $extra = []): never
    {
        $user = $this->requireUser();

        if (!isset(self::SECTIONS[$section])) {
            View::renderError('Страницата не е намерена.', 404);
        }

        $flash = StoreAuth::pullFlash();
        $message = $extra['message'] ?? ($flash['message'] ?? null);
        $isError = array_key_exists('isError', $extra)
            ? (bool) $extra['isError']
            : (bool) ($flash['error'] ?? false);

        $this->view('account', [
            'title' => self::SECTIONS[$section] . ' · Акаунт · Borz33',
            'section' => $section,
            'user' => $user,
            'profile' => $extra['profile'] ?? [
                'first_name' => (string) $user->first_name,
                'last_name' => (string) $user->last_name,
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
            ],
            'profileErrors' => $extra['profileErrors'] ?? [],
            'passwordErrors' => $extra['passwordErrors'] ?? [],
            'message' => $message,
            'isError' => $isError,
        ]);
    }
}
