<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Exceptions\ValidationException;
use App\Models\MediaFile;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Services\Products\ProductImageStorage;
use App\Validation\ProductImageValidator;

class UserAvatarService
{
    public function __construct(
        private readonly ProductImageStorage $storage = new ProductImageStorage(),
        private readonly ProductImageValidator $validator = new ProductImageValidator(),
        private readonly MediaService $media = new MediaService()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function store(User $user, array $file): User
    {
        $this->validator->validateUpload($file);

        return $this->attach($user, $this->media->store($file));
    }

    public function attach(User $user, MediaFile $media): User
    {
        if (!isset(ProductImageStorage::MIME_EXTENSIONS[$media->mime])) {
            throw new ValidationException(['image' => ['Разрешени са JPEG, PNG и WebP.']]);
        }

        $this->releaseFile($user);
        $user->forceFill([
            'avatar_path' => $media->path,
            'avatar_media_id' => $media->id,
        ])->save();

        return $user->fresh() ?? $user;
    }

    public function delete(User $user): User
    {
        $this->releaseFile($user);
        $user->forceFill([
            'avatar_path' => null,
            'avatar_media_id' => null,
        ])->save();

        return $user->fresh() ?? $user;
    }

    private function releaseFile(User $user): void
    {
        if ($user->avatar_media_id) {
            return;
        }

        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            $this->storage->deleteFile($user->avatar_path);
        }
    }
}
