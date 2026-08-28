<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Exceptions\ValidationException;
use App\Models\MediaFile;
use App\Services\Products\ProductImageStorage;
use Illuminate\Support\Str;

class MediaStorage
{
    /** @var list<string> */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'fcgi',
        'exe', 'bat', 'cmd', 'com', 'scr', 'pif', 'msi',
        'htaccess', 'htpasswd', 'html', 'htm', 'xhtml', 'js', 'mjs', 'svg',
    ];

    /** @var list<string> */
    private const BLOCKED_MIMES = [
        'text/html',
        'image/svg+xml',
        'application/javascript',
        'text/javascript',
        'application/x-httpd-php',
        'application/x-php',
        'text/x-php',
        'application/x-msdownload',
        'application/x-dosexec',
        'application/x-executable',
    ];

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
    ];

    public function __construct(
        private readonly ProductImageStorage $images = new ProductImageStorage()
    ) {
    }

    /**
     * @param array<string, mixed> $file
     * @return array{path: string, mime: string, size: int, extension: string, original_name: string}
     */
    public function store(array $file): array
    {
        $original = $this->originalName($file);
        $mime = $this->detectMime($file);
        $extension = $this->extension($original, $mime);

        if ($this->isBlocked($extension, $mime)) {
            throw new ValidationException(['file' => ['Този тип файл не е разрешен.']]);
        }

        $directory = 'uploads/media/' . date('Y/m');
        $absoluteDirectory = $this->images->publicRoot() . '/' . $directory;
        $this->images->ensureDirectory($absoluteDirectory, 'file');

        $filename = $this->uniqueFilename($absoluteDirectory, $this->safeStem($original), $extension);
        $relative = $directory . '/' . $filename;
        $target = $this->images->absolutePath($relative);
        $tmp = (string) $file['tmp_name'];

        if (is_uploaded_file($tmp)) {
            $moved = move_uploaded_file($tmp, $target);
        } else {
            $moved = copy($tmp, $target);
        }

        if (!$moved) {
            throw new ValidationException(['file' => ['Файлът не можа да се запише.']]);
        }

        return [
            'path' => $relative,
            'mime' => $mime,
            'size' => (int) ($file['size'] ?? filesize($target) ?: 0),
            'extension' => $extension,
            'original_name' => $original,
        ];
    }

    public function deleteFile(string $relativePath): void
    {
        $this->images->deleteFile($relativePath);
    }

    public function detectMime(array $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string) $file['tmp_name']);

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    public static function kindFor(string $mime, string $extension): string
    {
        if (str_starts_with($mime, 'image/')) {
            return MediaFile::KIND_IMAGE;
        }

        if (str_starts_with($mime, 'video/')) {
            return MediaFile::KIND_VIDEO;
        }

        if (str_starts_with($mime, 'audio/')) {
            return MediaFile::KIND_AUDIO;
        }

        $documents = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'csv', 'rtf', 'zip', 'rar', '7z', 'json', 'xml'];

        if (in_array($extension, $documents, true) || str_starts_with($mime, 'text/') || str_contains($mime, 'pdf') || str_contains($mime, 'officedocument') || str_contains($mime, 'msword') || str_contains($mime, 'spreadsheet') || str_contains($mime, 'zip')) {
            return MediaFile::KIND_DOCUMENT;
        }

        return MediaFile::KIND_OTHER;
    }

    /** @param array<string, mixed> $file */
    private function originalName(array $file): string
    {
        $name = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'file')));
        $name = trim($name);

        return $name !== '' ? substr($name, 0, 255) : 'file';
    }

    private function extension(string $original, string $mime): string
    {
        $fromName = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
        $fromName = preg_replace('/[^a-z0-9]+/', '', $fromName) ?? '';

        if ($fromName !== '') {
            return substr($fromName, 0, 32);
        }

        return self::MIME_EXTENSIONS[$mime] ?? '';
    }

    private function isBlocked(string $extension, string $mime): bool
    {
        if ($extension !== '' && in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return true;
        }

        $mime = strtolower($mime);

        return in_array($mime, self::BLOCKED_MIMES, true);
    }

    private function safeStem(string $stem): string
    {
        $base = pathinfo($stem, PATHINFO_FILENAME);
        $value = Str::slug((string) $base, '-', 'bg');

        return $value !== '' ? substr($value, 0, 80) : 'file';
    }

    private function uniqueFilename(string $directory, string $stem, string $extension): string
    {
        $suffix = bin2hex(random_bytes(4));
        $candidate = $extension === '' ? $stem . '-' . $suffix : $stem . '-' . $suffix . '.' . $extension;
        $tries = 0;

        while (is_file($directory . '/' . $candidate)) {
            $tries++;
            $suffix = bin2hex(random_bytes(4));
            $candidate = $extension === '' ? $stem . '-' . $suffix : $stem . '-' . $suffix . '.' . $extension;

            if ($tries > 20) {
                throw new ValidationException(['file' => ['Файлът не можа да се запише.']]);
            }
        }

        return $candidate;
    }
}
