<?php

declare(strict_types=1);

namespace Store\Controllers;

use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Resources\UserResource;
use App\Services\Users\AccountService;
use App\Services\Users\BillingAddressService;
use App\Services\Users\UserAvatarService;
use App\Services\Messages\ContactReplyNotificationService;
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
        'messages' => 'Съобщения',
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

    public function replyMessage(string $id): never
    {
        $user = $this->requireUser();
        $this->assertCsrf();
        $message = ContactMessage::query()->where('user_id', $user->id)->find((int) $id);
        if ($message === null) View::renderError('Разговорът не е намерен.', 404);

        $body = trim((string) Request::input('body', ''));
        if (mb_strlen($body) < 2 || mb_strlen($body) > 10000) {
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'message' => 'Отговорът трябва да бъде между 2 и 10 000 знака.'], 422);
            }
            $this->renderSection('messages', ['message' => 'Отговорът трябва да бъде между 2 и 10 000 знака.', 'isError' => true]);
        }

        $reply = ContactMessageReply::query()->create([
            'contact_message_id' => $message->id,
            'sender_type' => 'customer',
            'sender_user_id' => $user->id,
            'body' => $body,
            'email_sent' => false,
        ]);
        $recentNotificationExists = ContactMessageReply::query()
            ->where('contact_message_id', $message->id)
            ->where('sender_type', 'customer')
            ->where('email_sent', true)
            ->where('id', '!=', $reply->id)
            ->where('created_at', '>=', \Carbon\Carbon::now('UTC')->subMinutes(10))
            ->exists();
        $sent = !$recentNotificationExists && (new ContactReplyNotificationService())->sendToAdmin($message, $reply);
        $notificationStatus = $recentNotificationExists ? 'suppressed' : ($sent ? 'sent' : 'failed');
        $responseMessage = match ($notificationStatus) {
            'sent' => 'Отговорът Ви е изпратен.',
            'suppressed' => 'Отговорът Ви е записан в разговора.',
            default => 'Отговорът е записан, но уведомителният имейл не можа да бъде изпратен.',
        };
        $reply->forceFill(['email_sent' => $sent])->save();
        $message->forceFill(['read_at' => null])->save();

        if ($this->wantsJson()) {
            $this->json([
                'success' => true,
                'message' => $responseMessage,
                'data' => [
                    'reply' => [
                        'id' => (int) $reply->id,
                        'body' => (string) $reply->body,
                        'sender' => 'Вие',
                        'created_at' => $reply->created_at?->timezone('Europe/Sofia')->format('d.m.Y, H:i'),
                        'created_at_iso' => $reply->created_at?->toIso8601String(),
                        'email_sent' => $sent,
                        'notification_status' => $notificationStatus,
                    ],
                ],
            ]);
        }

        StoreAuth::setFlash($responseMessage, $notificationStatus === 'failed');
        $this->redirect('/account/messages?conversation=' . $message->id . '#conversation');
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

        StoreAuth::setFlash('Адресът е записан.');
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

        StoreAuth::setFlash('Адресът е обновен.');
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
        StoreAuth::setFlash('Основният адрес за този тип е сменен.');
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
        $ordersPerPage = 10;
        $ordersPage = max(1, (int) Request::query('page', 1));
        $orderStatus = (string) Request::query('status', 'all');
        $validOrderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'paid', 'cancelled'];
        if (!in_array($orderStatus, $validOrderStatuses, true)) $orderStatus = 'all';
        $ordersQuery = $user->orders()->with('items.product.frontImage');
        if ($orderStatus !== 'all') $ordersQuery->where('status', $orderStatus);
        $filteredOrderCount = $section === 'orders' ? $ordersQuery->count() : 0;
        $ordersLastPage = max(1, (int) ceil($filteredOrderCount / $ordersPerPage));
        $ordersPage = min($ordersPage, $ordersLastPage);
        $orders = $section === 'orders'
            ? $ordersQuery->forPage($ordersPage, $ordersPerPage)->get()
            : collect();
        $contactMessages = $section === 'messages'
            ? ContactMessage::query()->where('user_id', $user->id)->with(['attachments.file', 'replies.admin', 'replies.sender', 'replies.attachments.file'])->orderByDesc('created_at')->orderByDesc('id')->get()
            : collect();
        $requestedConversation = (int) Request::query('conversation', 0);
        $activeContactMessage = $contactMessages->firstWhere('id', $requestedConversation) ?? $contactMessages->first();
        $conversationStarted = $section === 'messages'
            && Request::query('started') === '1'
            && $activeContactMessage !== null
            && (int) $activeContactMessage->id === $requestedConversation;

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
            'ordersPagination' => ['page' => $ordersPage, 'lastPage' => $ordersLastPage, 'status' => $orderStatus, 'filteredCount' => $filteredOrderCount],
            'contactMessages' => $contactMessages,
            'activeContactMessage' => $activeContactMessage,
            'conversationStarted' => $conversationStarted,
            'conversationEmailSent' => Request::query('email') === '1',
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
