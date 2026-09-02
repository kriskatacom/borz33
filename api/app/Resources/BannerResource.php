<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Banner;

class BannerResource
{
    /** @return array<string, mixed> */
    public static function toAdminArray(Banner $banner): array
    {
        $banner->loadMissing(['mediaFile', 'buttons']);

        return [
            ...self::toAdminListArray($banner),
            'text' => $banner->text,
            'buttons' => BannerButtonResource::collection($banner->buttons),
        ];
    }

    /** @return array<string, mixed> */
    public static function toAdminListArray(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'slug' => $banner->slug,
            'layout' => $banner->layoutKey(),
            'height' => $banner->height,
            'width_mode' => $banner->width_mode ?: 'container',
            'image_position' => $banner->image_position ?: 'center',
            'content_position' => $banner->content_position ?: 'center',
            'media_file_id' => $banner->media_file_id,
            'media' => $banner->mediaFile ? MediaFileResource::toArray($banner->mediaFile) : null,
            'is_active' => $banner->is_active,
            'sort_order' => $banner->sort_order,
            'buttons_count' => (int) ($banner->buttons_count ?? $banner->buttons()->count()),
            'created_at' => $banner->created_at?->toIso8601String(),
            'updated_at' => $banner->updated_at?->toIso8601String(),
            'deleted_at' => $banner->deleted_at?->toIso8601String(),
        ];
    }

    /** @param iterable<int, Banner> $banners */
    public static function collection(iterable $banners): array
    {
        $items = [];

        foreach ($banners as $banner) {
            $items[] = self::toAdminListArray($banner);
        }

        return $items;
    }
}
