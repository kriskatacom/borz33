<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Services\Media\MediaStorage;

class MediaFileValidator
{
    private const MAX_BYTES = 128 * 1024 * 1024;

    public function __construct(
        private readonly MediaStorage $storage = new MediaStorage()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function validateUpload(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new ValidationException(['file' => ['Файлът трябва да е най-много 128 MB.']]);
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException(['file' => ['Файлът не можа да се качи.']]);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_file($tmp)) {
            throw new ValidationException(['file' => ['Изберете файл.']]);
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new ValidationException(['file' => ['Файлът трябва да е най-много 128 MB.']]);
        }

        $name = (string) ($file['name'] ?? '');
        $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?? '';
        $mime = $this->storage->detectMime($file);

        if (in_array($extension, MediaStorage::BLOCKED_EXTENSIONS, true)) {
            throw new ValidationException(['file' => ['Този тип файл не е разрешен.']]);
        }

        $this->assertNotBlockedMime($mime);
    }

    /** @param array<string, mixed> $data */
    public function validateMeta(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'original_name' => ['sometimes', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
        ], [], [
            'original_name' => 'име на файла',
            'alt' => 'алтернативен текст',
            'title' => 'заглавие на изображението',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    private function assertNotBlockedMime(string $mime): void
    {
        $blocked = [
            'text/html',
            'image/svg+xml',
            'application/javascript',
            'text/javascript',
            'application/x-httpd-php',
            'application/x-php',
            'text/x-php',
        ];

        if (in_array(strtolower($mime), $blocked, true)) {
            throw new ValidationException(['file' => ['Този тип файл не е разрешен.']]);
        }
    }
}
