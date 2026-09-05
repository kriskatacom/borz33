<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Core\Auth;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Banner;
use App\Models\MediaFile;
use App\Models\ProductImage;
use App\Models\User;
use App\Resources\MediaFileResource;
use App\Services\Products\ProductImageStorage;
use App\Validation\MediaFileValidator;
use Illuminate\Database\Eloquent\Builder;

class MediaService
{
    public function __construct(
        private readonly MediaStorage $storage = new MediaStorage(),
        private readonly MediaFileValidator $validator = new MediaFileValidator()
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 24)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $files = $query
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'files' => MediaFileResource::collection($files),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id): MediaFile
    {
        $file = MediaFile::query()->find($id);

        if ($file === null) {
            throw new AuthException('Файлът не е намерен.', 404);
        }

        return $file;
    }

    public function requireRasterImage(int $id): MediaFile
    {
        $file = $this->find($id);

        if (!isset(ProductImageStorage::MIME_EXTENSIONS[$file->mime])) {
            throw new ValidationException(['image' => ['Разрешени са JPEG, PNG и WebP.']]);
        }

        return $file;
    }

    /** @param array<string, mixed> $file */
    public function store(array $file, ?int $originalSize = null): MediaFile
    {
        $this->validator->validateUpload($file);
        $stored = $this->storage->store($file);
        $media = new MediaFile();
        $media->forceFill([
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime' => $stored['mime'],
            'extension' => $stored['extension'],
            'kind' => MediaStorage::kindFor($stored['mime'], $stored['extension']),
            'size' => $stored['size'],
            'original_size' => $originalSize !== null && $originalSize >= $stored['size'] ? $originalSize : null,
            'alt' => null,
            'title' => null,
            'width' => $stored['width'],
            'height' => $stored['height'],
            'uploaded_by' => Auth::user()?->id,
        ]);
        $media->save();

        return $media->fresh() ?? $media;
    }

    /** @param array<string, mixed> $data */
    public function update(MediaFile $file, array $data): MediaFile
    {
        $payload = $this->validator->validateMeta($data);

        if (array_key_exists('original_name', $payload) && is_string($payload['original_name'])) {
            $name = trim($payload['original_name']);
            $file->original_name = $name !== '' ? $name : $file->original_name;
        }

        if (array_key_exists('alt', $payload)) {
            $alt = $payload['alt'];
            $file->alt = is_string($alt) && trim($alt) !== '' ? trim($alt) : null;
        }

        if (array_key_exists('title', $payload)) {
            $title = $payload['title'];
            $file->title = is_string($title) && trim($title) !== '' ? trim($title) : null;
        }

        $file->save();

        return $file->fresh() ?? $file;
    }

    public function delete(MediaFile $file): void
    {
        if (Banner::query()->where('media_file_id', $file->id)->exists()) {
            throw new AuthException('Файлът се използва от банер и не може да бъде изтрит.', 409);
        }

        ProductImage::query()->where('media_file_id', $file->id)->delete();
        User::query()->where('avatar_media_id', $file->id)->update([
            'avatar_path' => null,
            'avatar_media_id' => null,
        ]);

        $path = $file->path;
        $file->delete();

        if (is_string($path) && $path !== '') {
            $this->storage->deleteFile($path);
        }
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $query = MediaFile::query();
        $search = trim((string) ($filters['q'] ?? ''));
        $kind = trim((string) ($filters['kind'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder->where('original_name', 'like', $like)
                    ->orWhere('extension', 'like', $like)
                    ->orWhere('mime', 'like', $like);
            });
        }

        $kinds = [
            MediaFile::KIND_IMAGE,
            MediaFile::KIND_VIDEO,
            MediaFile::KIND_AUDIO,
            MediaFile::KIND_DOCUMENT,
            MediaFile::KIND_OTHER,
        ];

        if (in_array($kind, $kinds, true)) {
            $query->where('kind', $kind);
        }

        $raster = strtolower(trim((string) ($filters['raster'] ?? '')));

        if (in_array($raster, ['1', 'true', 'yes', 'on'], true)) {
            $query->whereIn('mime', array_keys(ProductImageStorage::MIME_EXTENSIONS));
        }

        return $query;
    }
}
