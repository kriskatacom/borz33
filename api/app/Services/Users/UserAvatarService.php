<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\User;
use App\Services\Products\ProductImageStorage;
use App\Validation\ProductImageValidator;

class UserAvatarService
{
    public function __construct(
        private readonly ProductImageStorage $storage = new ProductImageStorage(),
        private readonly ProductImageValidator $validator = new ProductImageValidator()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function store(User $user, array $file): User
    {
        $this->validator->validateUpload($file);
        $previous = $user->avatar_path;
        $stored = $this->storage->storeNamed(
            'uploads/users/' . $user->id,
            $file,
            trim($user->fullName()) !== '' ? $user->fullName() : 'avatar'
        );

        $user->forceFill(['avatar_path' => $stored['path']])->save();

        if (is_string($previous) && $previous !== '' && $previous !== $stored['path']) {
            $this->storage->deleteFile($previous);
        }

        return $user->fresh() ?? $user;
    }

    public function delete(User $user): User
    {
        if (is_string($user->avatar_path) && $user->avatar_path !== '') {
            $this->storage->deleteFile($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();

        return $user->fresh() ?? $user;
    }
}
