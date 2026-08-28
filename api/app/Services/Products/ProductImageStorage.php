<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Exceptions\ValidationException;
use Illuminate\Support\Str;

class ProductImageStorage
{
    /** @var array<string, string> */
    public const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function publicRoot(): string
    {
        return dirname(__DIR__, 3) . '/public';
    }

    public function absolutePath(string $relativePath): string
    {
        return $this->publicRoot() . '/' . ltrim($relativePath, '/');
    }

    public function directory(int $productId): string
    {
        return 'uploads/products/' . $productId;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{path: string, mime: string}
     */
    public function store(int $productId, array $file, string $stem): array
    {
        return $this->storeNamed($this->directory($productId), $file, $stem);
    }

    /**
     * @param array<string, mixed> $file
     * @return array{path: string, mime: string}
     */
    public function storeNamed(string $directory, array $file, string $stem): array
    {
        $mime = $this->detectMime($file);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;

        if ($extension === null) {
            throw new ValidationException(['image' => ['Разрешени са JPEG, PNG и WebP.']]);
        }

        $directory = trim($directory, '/');
        $absoluteDirectory = $this->publicRoot() . '/' . $directory;
        $this->ensureDirectory($absoluteDirectory);

        $filename = $this->uniqueFilename($absoluteDirectory, $this->safeStem($stem), $extension);
        $relative = $directory . '/' . $filename;
        $target = $this->absolutePath($relative);
        $tmp = (string) $file['tmp_name'];

        if (is_uploaded_file($tmp)) {
            $moved = move_uploaded_file($tmp, $target);
        } else {
            $moved = copy($tmp, $target);
        }

        if (!$moved) {
            throw new ValidationException(['image' => ['Изображението не можа да се запише.']]);
        }

        return ['path' => $relative, 'mime' => $mime];
    }

    public function deleteFile(string $relativePath): void
    {
        $absolute = $this->absolutePath($relativePath);

        if (is_file($absolute)) {
            unlink($absolute);
        }
    }

    public function deleteProductDirectory(int $productId): void
    {
        $directory = $this->publicRoot() . '/' . $this->directory($productId);

        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_file($path)) {
                unlink($path);
            }
        }

        @rmdir($directory);
    }

    public function ensureDirectory(string $absoluteDirectory, string $errorKey = 'image'): void
    {
        if (is_dir($absoluteDirectory)) {
            return;
        }

        $created = @mkdir($absoluteDirectory, 0775, true);

        if ($created || is_dir($absoluteDirectory)) {
            return;
        }

        throw new ValidationException([
            $errorKey => [$errorKey === 'file' ? 'Файлът не можа да се запише.' : 'Изображението не можа да се запише.'],
        ]);
    }

    private function safeStem(string $stem): string
    {
        $value = Str::slug($stem, '-', 'bg');

        return $value !== '' ? substr($value, 0, 160) : 'image';
    }

    private function uniqueFilename(string $directory, string $stem, string $extension): string
    {
        $candidate = $stem . '.' . $extension;
        $suffix = 2;

        while (is_file($directory . '/' . $candidate)) {
            $candidate = $stem . '-' . $suffix . '.' . $extension;
            $suffix++;

            if ($suffix > 9999) {
                throw new ValidationException(['image' => ['Изображението не можа да се запише.']]);
            }
        }

        return $candidate;
    }

    /** @param array<string, mixed> $file */
    public function detectMime(array $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);

        return is_string($mime) ? $mime : 'application/octet-stream';
    }
}
