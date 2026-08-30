<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Models\UserAddress;
use App\Resources\UserResource;
use App\Services\Users\AccountService;
use App\Services\Users\BillingAddressService;
use App\Services\Users\UserAvatarService;
use App\Validation\BillingAddressValidator;
use App\Validation\ChangeAccountPasswordValidator;
use App\Validation\UpdateAccountProfileValidator;
use Store\Core\StoreAuth;
use Store\Core\View;

class AccountController extends Controller
{
    public const SECTIONS = [
        'dashboard' => 'Табло',
        'profile' => 'Профил',
        'details' => 'Данни на акаунта',
        'password' => 'Парола',
        'orders' => 'Поръчки',
        'addresses' => 'Адреси',
        'appearance' => 'Изглед',
    ];

    public function __construct(
        private readonly AccountService $accounts = new AccountService(),
        private readonly UpdateAccountProfileValidator $profileValidator = new UpdateAccountProfileValidator(),
        private readonly ChangeAccountPasswordValidator $passwordValidator = new ChangeAccountPasswordValidator(),
        private readonly BillingAddressValidator $addressValidator = new BillingAddressValidator(),
        private readonly BillingAddressService $addresses = new BillingAddressService(),
        private readonly UserAvatarService $avatars = new UserAvatarService()
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
            'phone' => Request::input('phone'),
        ];

        try {
            $payload = $this->profileValidator->validate($input);
            $this->accounts->updateProfile($user, $payload);
        } catch (ValidationException $exception) {
            $this->renderSection('details', [
                'profile' => [
                    'first_name' => (string) ($input['first_name'] ?? ''),
                    'last_name' => (string) ($input['last_name'] ?? ''),
                    'email' => (string) $user->email,
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

    public function storeAddress(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $input = $this->addressInput();

        try {
            $payload = $this->addressValidator->validate($input);
            $this->addresses->create($user, $payload);
        } catch (ValidationException $exception) {
            $this->renderSection('addresses', [
                'addressForm' => $input,
                'addressErrors' => $exception->errors(),
                'editingAddressId' => null,
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        StoreAuth::setFlash('Адресът за фактуриране е записан.');
        $this->redirect('/account/addresses');
    }

    public function updateAddress(string $id): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $address = $this->ownedAddress($user, $id);
        $input = $this->addressInput();

        try {
            $payload = $this->addressValidator->validate($input);
            $this->addresses->update($user, $address, $payload);
        } catch (ValidationException $exception) {
            $this->renderSection('addresses', [
                'addressForm' => $input,
                'addressErrors' => $exception->errors(),
                'editingAddressId' => (int) $address->id,
                'message' => $exception->getMessage(),
                'isError' => true,
            ]);
        }

        StoreAuth::setFlash('Адресът за фактуриране е обновен.');
        $this->redirect('/account/addresses');
    }

    public function destroyAddress(string $id): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $this->addresses->delete($user, $this->ownedAddress($user, $id));
        StoreAuth::setFlash('Адресът е изтрит.');
        $this->redirect('/account/addresses');
    }

    public function makeDefaultAddress(string $id): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $this->addresses->setDefault($user, $this->ownedAddress($user, $id));
        StoreAuth::setFlash('Основният адрес за фактуриране е сменен.');
        $this->redirect('/account/addresses');
    }

    public function updateAvatar(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();

        try {
            $preset = Request::input('preset');

            if (is_string($preset) && trim($preset) !== '') {
                $user = $this->avatars->attachPreset($user, $preset);
                $this->jsonAvatar($user, 'Профилната снимка е записана.');
            }

            $file = Request::file('image');

            if ($file === null) {
                throw new ValidationException(['image' => ['Изберете изображение или готов аватар.']]);
            }

            $user = $this->avatars->store($user, $file);
            $this->jsonAvatar($user, 'Профилната снимка е записана.');
        } catch (ValidationException $exception) {
            $this->json([
                'message' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Снимката не можа да се запише.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    public function destroyAvatar(): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $user = $this->avatars->delete($user);
        $this->jsonAvatar($user, 'Профилната снимка е премахната.');
    }

    private function jsonAvatar(User $user, string $message): never
    {
        $this->json([
            'message' => $message,
            'data' => [
                'avatar_url' => UserResource::avatarUrl($user),
            ],
        ]);
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
        $orderCount = $user->orders()->count();
        $orders = $section === 'orders'
            ? $user->orders()->with('items')->limit(50)->get()
            : collect();

        $this->view('account', [
            'title' => self::SECTIONS[$section] . ' · Акаунт · Borz33',
            'section' => $section,
            'user' => $user,
            'avatarUrl' => UserResource::avatarUrl($user),
            'avatarPresets' => $this->avatars->presets(),
            'profile' => $extra['profile'] ?? [
                'first_name' => (string) $user->first_name,
                'last_name' => (string) $user->last_name,
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
            ],
            'profileErrors' => $extra['profileErrors'] ?? [],
            'passwordErrors' => $extra['passwordErrors'] ?? [],
            'billingAddresses' => $this->addresses->list($user),
            'addressForm' => $extra['addressForm'] ?? $this->defaultAddressForm($user, $extra),
            'addressErrors' => $extra['addressErrors'] ?? [],
            'orders' => $orders,
            'orderCount' => $orderCount,
            'editingAddressId' => array_key_exists('editingAddressId', $extra)
                ? $extra['editingAddressId']
                : $this->requestedEditId($user),
            'message' => $message,
            'isError' => $isError,
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function defaultAddressForm(User $user, array $extra): array
    {
        $editId = $extra['editingAddressId'] ?? $this->requestedEditId($user);

        if (is_int($editId) && $editId > 0) {
            $address = $this->addresses->findOwned($user, $editId);

            if ($address !== null) {
                return $this->addresses->toForm($address);
            }
        }

        return $this->addresses->emptyForm($user);
    }

    private function requestedEditId(User $user): ?int
    {
        $raw = Request::query('edit');

        if (!is_numeric($raw)) {
            return null;
        }

        $id = (int) $raw;
        $address = $this->addresses->findOwned($user, $id);

        return $address?->id;
    }

    private function ownedAddress(User $user, string $id): UserAddress
    {
        $address = $this->addresses->findOwned($user, (int) $id);

        if ($address === null) {
            View::renderError('Адресът не е намерен.', 404);
        }

        return $address;
    }

    /** @return array<string, mixed> */
    private function addressInput(): array
    {
        return [
            'party' => Request::input('party'),
            'label' => Request::input('label'),
            'first_name' => Request::input('first_name'),
            'last_name' => Request::input('last_name'),
            'company_name' => Request::input('company_name'),
            'eik' => Request::input('eik'),
            'vat_number' => Request::input('vat_number'),
            'mol' => Request::input('mol'),
            'line1' => Request::input('line1'),
            'city' => Request::input('city'),
            'postal_code' => Request::input('postal_code'),
            'country' => Request::input('country', 'България'),
            'is_default' => Request::input('is_default'),
        ];
    }
}
