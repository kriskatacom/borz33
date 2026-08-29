<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Exceptions\ValidationException;
use App\Resources\UserResource;
use App\Services\Media\MediaService;
use App\Services\Users\UserAdminService;
use App\Services\Users\UserAvatarService;
use App\Validation\StoreUserValidator;
use App\Validation\UpdateUserValidator;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserAdminService $users = new UserAdminService(),
        private readonly StoreUserValidator $storeValidator = new StoreUserValidator(),
        private readonly UpdateUserValidator $updateValidator = new UpdateUserValidator(),
        private readonly UserAvatarService $avatars = new UserAvatarService(),
        private readonly MediaService $media = new MediaService()
    ) {
    }

    public function index(): never
    {
        $this->ok($this->users->paginate(Request::query()), 'Списък с потребители.');
    }

    public function show(string $id): never
    {
        $user = $this->users->find($this->id($id), true);

        $this->ok(['user' => UserResource::toAdminArray($user)]);
    }

    public function store(): never
    {
        $payload = $this->storeValidator->validate(Request::input());
        $user = $this->users->create($payload);

        $this->created(['user' => UserResource::toAdminArray($user)], 'Потребителят е създаден.');
    }

    public function update(string $id): never
    {
        $user = $this->users->find($this->id($id));
        $payload = $this->updateValidator->validate(Request::input(), $user->id);
        $user = $this->users->update($user, $payload);

        $this->ok(['user' => UserResource::toAdminArray($user)], 'Потребителят е обновен.');
    }

    public function destroy(string $id): never
    {
        $this->users->delete($this->users->find($this->id($id)));

        $this->ok([], 'Потребителят е изтрит.');
    }

    public function restore(string $id): never
    {
        $user = $this->users->restore($this->id($id));

        $this->ok(['user' => UserResource::toAdminArray($user)], 'Потребителят е възстановен.');
    }

    public function avatarPresets(): never
    {
        $this->ok(['presets' => $this->avatars->presets()]);
    }

    public function storeAvatar(string $id): never
    {
        $user = $this->users->find($this->id($id));
        $preset = Request::input('preset');

        if (is_string($preset) && trim($preset) !== '') {
            $user = $this->avatars->attachPreset($user, $preset);
            $this->created(['user' => UserResource::toAdminArray($user)], 'Профилната снимка е записана.');
        }

        $mediaId = Request::input('media_id');

        if (is_int($mediaId) || (is_string($mediaId) && ctype_digit($mediaId))) {
            $user = $this->avatars->attach($user, $this->media->requireRasterImage((int) $mediaId));
            $this->created(['user' => UserResource::toAdminArray($user)], 'Профилната снимка е записана.');
        }

        $file = Request::file('image');

        if ($file === null) {
            throw new ValidationException(['image' => ['Изберете изображение или файл от медията.']]);
        }

        $user = $this->avatars->store($user, $file);

        $this->created(['user' => UserResource::toAdminArray($user)], 'Профилната снимка е записана.');
    }

    public function destroyAvatar(string $id): never
    {
        $user = $this->avatars->delete($this->users->find($this->id($id)));

        $this->ok(['user' => UserResource::toAdminArray($user)], 'Профилната снимка е премахната.');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            $this->error('Потребителят не е намерен.', 404);
        }

        return (int) $id;
    }
}
