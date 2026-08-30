<?php

declare(strict_types=1);

namespace Store\Core;

use App\Models\Banner;
use App\Support\SafeHtml;

class Banners
{
    public static function imagePath(string $slug): ?string
    {
        try {
            $banner = Banner::query()
                ->where('slug', strtolower(trim($slug)))
                ->where('is_active', true)
                ->with('mediaFile')
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if ($banner?->mediaFile === null) {
            return null;
        }

        return '/' . ltrim((string) $banner->mediaFile->path, '/');
    }

    public static function render(string $slug): void
    {
        $banner = Banner::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('is_active', true)
            ->with(['mediaFile', 'buttons'])
            ->first();

        if ($banner === null || $banner->mediaFile === null) {
            return;
        }

        $buttons = [];

        foreach ($banner->buttons as $button) {
            $url = self::safeUrl((string) $button->url);

            if ($url === null || trim((string) $button->label) === '') {
                continue;
            }

            $buttons[] = $button;
        }

        if ($buttons === []) {
            return;
        }

        $file = dirname(__DIR__, 2) . '/views/partials/banner.php';

        require $file;
    }

    public static function safeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if ($scheme === 'http' || $scheme === 'https') {
            return $url;
        }

        return null;
    }

    public static function textHtml(string $text): string
    {
        return SafeHtml::bannerText($text);
    }
}
