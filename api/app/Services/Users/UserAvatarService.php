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

    public function attachPreset(User $user, string $preset): User
    {
        $path = $this->presetPath($preset);

        if ($path === null) {
            throw new ValidationException(['preset' => ['Този аватар не е наличен.']]);
        }

        $this->releaseFile($user);
        $user->forceFill([
            'avatar_path' => $path,
            'avatar_media_id' => null,
        ])->save();

        return $user->fresh() ?? $user;
    }

    /** @return list<array{id: string, url: string, label: string}> */
    public function presets(): array
    {
        $items = [];

        foreach ($this->presetFilenames() as $name) {
            $items[] = [
                'id' => $name,
                'url' => '/assets/' . rawurlencode($name),
                'label' => $this->presetLabel($name),
            ];
        }

        return $items;
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

        if (!is_string($user->avatar_path) || $user->avatar_path === '') {
            return;
        }

        if ($this->isPresetPath($user->avatar_path)) {
            return;
        }

        $this->storage->deleteFile($user->avatar_path);
    }

    private function isPresetPath(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), 'assets/');
    }

    private function presetPath(string $preset): ?string
    {
        $name = basename(str_replace('\\', '/', $preset));

        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }

        if (!in_array($name, $this->presetFilenames(), true)) {
            return null;
        }

        return 'assets/' . $name;
    }

    /** @return list<string> */
    private function presetFilenames(): array
    {
        $directory = $this->storage->publicRoot() . '/assets';

        if (!is_dir($directory)) {
            return [];
        }

        $names = [];

        foreach (scandir($directory) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            if (!is_file($directory . '/' . $name)) {
                continue;
            }

            if (preg_match('/\.(jpe?g|png|webp)$/i', $name) !== 1) {
                continue;
            }

            $names[] = $name;
        }

        natcasesort($names);

        return array_values($names);
    }

    private function presetLabel(string $filename): string
    {
        $stem = (string) preg_replace('/\.(jpe?g|png|webp)$/i', '', $filename);
        $known = [
            'cat' => 'Котка',
            'man' => 'Мъж',
            'profile' => 'Профил',
            'user' => 'Потребител',
            'user (1)' => 'Потребител 1',
            'user (2)' => 'Потребител 2',
            'user (3)' => 'Потребител 3',
        ];

        return $known[$stem] ?? $stem;
    }
}
