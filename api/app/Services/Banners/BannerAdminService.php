<?php

declare(strict_types=1);

namespace App\Services\Banners;

use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Models\Banner;
use App\Models\BannerButton;
use App\Resources\BannerResource;
use App\Services\Media\MediaService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BannerAdminService
{
    public function __construct(
        private readonly MediaService $media = new MediaService()
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);
        $total = (clone $query)->count();
        $banners = $query
            ->with(['mediaFile'])
            ->withCount('buttons')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'banners' => BannerResource::collection($banners),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    public function find(int $id, bool $withTrashed = false): Banner
    {
        $query = $withTrashed ? Banner::withTrashed() : Banner::query();
        $banner = $query->find($id);

        if ($banner === null) {
            throw new AuthException('Банерът не е намерен.', 404);
        }

        return $banner;
    }

    public function findBySlug(string $slug, bool $activeOnly = false): Banner
    {
        $query = Banner::query()->where('slug', strtolower(trim($slug)));

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $banner = $query->first();

        if ($banner === null) {
            throw new AuthException('Банерът не е намерен.', 404);
        }

        return $banner;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Banner
    {
        return Capsule::connection()->transaction(function () use ($data): Banner {
            $banner = new Banner();
            $banner->forceFill($this->bannerAttributes($data, null, null))->save();
            $this->syncButtons($banner, $data);

            return $this->fresh($banner);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Banner $banner, array $data): Banner
    {
        return Capsule::connection()->transaction(function () use ($banner, $data): Banner {
            $attributes = $this->bannerAttributes($data, (int) $banner->id, $banner);

            if ($attributes !== []) {
                $banner->forceFill($attributes)->save();
            }

            $this->syncButtons($banner, $data);

            return $this->fresh($banner);
        });
    }

    public function delete(Banner $banner): void
    {
        if ($banner->trashed()) {
            throw new AuthException('Банерът вече е изтрит.');
        }

        $banner->delete();
    }

    public function restore(int $id): Banner
    {
        $banner = $this->find($id, true);

        if ($banner->deleted_at === null) {
            throw new AuthException('Банерът не е изтрит.');
        }

        $banner->restore();

        return $this->fresh($banner);
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $status = (string) ($filters['status'] ?? 'all');
        $query = match ($status) {
            'deleted' => Banner::onlyTrashed(),
            'inactive' => Banner::query()->where('is_active', false),
            'active' => Banner::query()->where('is_active', true),
            default => Banner::query(),
        };

        $search = trim((string) ($filters['q'] ?? ''));

        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(static function (Builder $builder) use ($like): void {
                $builder
                    ->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('text', 'like', $like);
            });
        }

        $slug = trim((string) ($filters['slug'] ?? ''));

        if ($slug !== '') {
            $query->where('slug', strtolower($slug));
        }

        return $query;
    }

    /** @param array<string, mixed> $data */
    private function bannerAttributes(array $data, ?int $bannerId, ?Banner $existing): array
    {
        if ($existing === null) {
            $slug = trim((string) ($data['slug'] ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) $data['title'], $bannerId);
            }

            return [
                'title' => $data['title'],
                'slug' => $slug,
                'text' => $data['text'],
                'layout' => $this->resolvedLayout($data['layout'] ?? null),
                'height' => array_key_exists('height', $data) && $data['height'] !== null ? (int) $data['height'] : null,
                'width_mode' => $this->resolvedWidthMode($data['width_mode'] ?? null),
                'image_position' => $this->resolvedImagePosition($data['image_position'] ?? null),
                'content_position' => $this->resolvedContentPosition($data['content_position'] ?? null),
                'media_file_id' => $this->resolvedMediaFileId($data['media_file_id'] ?? null),
                'is_active' => (bool) $data['is_active'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ];
        }

        $attributes = [];

        if (array_key_exists('title', $data)) {
            $attributes['title'] = $data['title'];
        }

        if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
            $slug = trim((string) ($data['slug'] ?? $existing->slug ?? ''));

            if ($slug === '') {
                $slug = $this->uniqueSlug((string) ($data['title'] ?? $existing->title), $bannerId);
            }

            $attributes['slug'] = $slug;
        }

        if (array_key_exists('text', $data)) {
            $attributes['text'] = $data['text'];
        }

        if (array_key_exists('layout', $data)) {
            $attributes['layout'] = $this->resolvedLayout($data['layout']);
        }

        if (array_key_exists('height', $data)) {
            $attributes['height'] = $data['height'] !== null ? (int) $data['height'] : null;
        }

        if (array_key_exists('width_mode', $data)) {
            $attributes['width_mode'] = $this->resolvedWidthMode($data['width_mode']);
        }

        if (array_key_exists('image_position', $data)) {
            $attributes['image_position'] = $this->resolvedImagePosition($data['image_position']);
        }

        if (array_key_exists('content_position', $data)) {
            $attributes['content_position'] = $this->resolvedContentPosition($data['content_position']);
        }

        if (array_key_exists('is_active', $data)) {
            $attributes['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('media_file_id', $data)) {
            $attributes['media_file_id'] = $this->resolvedMediaFileId($data['media_file_id']);
        }

        if (array_key_exists('sort_order', $data)) {
            $attributes['sort_order'] = (int) $data['sort_order'];
        }

        return $attributes;
    }

    /** @param array<string, mixed> $data */
    private function syncButtons(Banner $banner, array $data): void
    {
        if (!array_key_exists('buttons', $data)) {
            return;
        }

        $rows = is_array($data['buttons']) ? $data['buttons'] : [];

        if ($rows === []) {
            BannerButton::query()->where('banner_id', $banner->id)->delete();
            return;
        }

        $keep = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $button = $this->ownedButton($banner, $row['id'] ?? null);
            $button->forceFill([
                'banner_id' => $banner->id,
                'label' => $row['label'],
                'url' => $row['url'],
                'open_in_new_tab' => (bool) ($row['open_in_new_tab'] ?? false),
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ])->save();
            $keep[] = (int) $button->id;
        }

        BannerButton::query()
            ->where('banner_id', $banner->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    private function ownedButton(Banner $banner, mixed $id): BannerButton
    {
        if (is_int($id) || (is_string($id) && ctype_digit($id))) {
            $button = BannerButton::query()
                ->where('banner_id', $banner->id)
                ->where('id', (int) $id)
                ->first();

            if ($button !== null) {
                return $button;
            }
        }

        return new BannerButton();
    }

    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title, '-', 'bg');
        $base = $base !== '' ? $base : 'banner';
        $candidate = $base;
        $suffix = 2;

        while (
            Banner::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId !== null, static fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function resolvedLayout(mixed $value): string
    {
        $layout = is_string($value) ? trim($value) : '';

        if (!in_array($layout, Banner::LAYOUTS, true)) {
            throw new ValidationException(['layout' => ['Изберете валиден дизайн.']]);
        }

        return $layout;
    }

    private function resolvedWidthMode(mixed $value): string
    {
        $mode = is_string($value) ? trim($value) : '';

        if (!in_array($mode, ['container', 'full'], true)) {
            throw new ValidationException(['width_mode' => ['Изберете валидна ширина на банера.']]);
        }

        return $mode;
    }

    private function resolvedImagePosition(mixed $value): string
    {
        $position = is_string($value) ? trim($value) : '';

        if (!array_key_exists($position, Banner::IMAGE_POSITIONS)) {
            throw new ValidationException(['image_position' => ['Изберете валидна позиция на изображението.']]);
        }

        return $position;
    }

    private function resolvedContentPosition(mixed $value): string
    {
        $position = is_string($value) ? trim($value) : '';

        if (!array_key_exists($position, Banner::CONTENT_POSITIONS)) {
            throw new ValidationException(['content_position' => ['Изберете валидна позиция на съдържанието.']]);
        }

        return $position;
    }

    private function resolvedMediaFileId(mixed $value): int
    {
        $mediaId = $this->nullableId($value);

        if ($mediaId === null) {
            throw new ValidationException(['media_file_id' => ['Банерът трябва да има изображение.']]);
        }

        try {
            $this->media->requireRasterImage($mediaId);
        } catch (ValidationException) {
            throw new ValidationException(['media_file_id' => ['Разрешени са JPEG, PNG и WebP.']]);
        }

        return $mediaId;
    }

    private function nullableId(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }

    private function fresh(Banner $banner): Banner
    {
        $fresh = Banner::query()->with(['mediaFile', 'buttons'])->withCount('buttons')->find($banner->id);

        return $fresh ?? $banner;
    }
}
