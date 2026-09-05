<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\ValidatorFactory;
use App\Exceptions\ValidationException;
use App\Services\Products\ProductImageStorage;

class ProductImageValidator
{
    private const MAX_BYTES = 128 * 1024 * 1024;

    public function __construct(
        private readonly ProductImageStorage $storage = new ProductImageStorage()
    ) {
    }

    /** @param array<string, mixed> $file */
    public function validateUpload(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException(['image' => ['Изображението не можа да се качи.']]);
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_file($tmp)) {
            throw new ValidationException(['image' => ['Изберете изображение.']]);
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new ValidationException(['image' => ['Изображението трябва да е най-много 128 MB.']]);
        }

        $mime = $this->storage->detectMime($file);

        if (!isset(ProductImageStorage::MIME_EXTENSIONS[$mime])) {
            throw new ValidationException(['image' => ['Разрешени са JPEG, PNG и WebP.']]);
        }
    }

    /** @param array<string, mixed> $data */
    public function validateMeta(array $data): array
    {
        $validator = ValidatorFactory::make()->make($data, [
            'alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [], [
            'alt' => 'алтернативен текст',
            'sort_order' => 'ред',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}
