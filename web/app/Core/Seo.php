<?php

declare(strict_types=1);

namespace Store\Core;

final class Seo
{
    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    public static function build(array $data): array
    {
        $siteName = trim((string) ($_ENV['COMPANY_NAME'] ?? 'Borz33')) ?: 'Borz33';
        $baseUrl = rtrim((string) ($_ENV['WEB_PUBLIC_URL'] ?? 'http://localhost:4000'), '/');
        $path = (string) ($data['currentPath'] ?? '/');
        $title = trim((string) ($data['title'] ?? $siteName));
        $description = trim((string) ($data['metaDescription'] ?? self::description($path, $siteName)));
        $robots = (string) ($data['robots'] ?? (self::isPrivate($path, (int) ($data['status'] ?? 200)) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'));
        $canonicalPath = (string) ($data['canonicalPath'] ?? $path);
        $pageValue = $data['paginationPage'] ?? $data['page'] ?? 1;
        $page = max(1, is_numeric($pageValue) ? (int) $pageValue : 1);
        $lastPage = max(1, (int) ($data['lastPage'] ?? 1));
        $query = trim((string) (\App\Core\Request::query('q') ?? ''));
        $params = [];
        $navigationParams = [];
        $hasCatalogFilters = str_starts_with($path, '/catalog') && self::hasCatalogFilters();

        if ($query !== '') {
            $params['q'] = $query;
            $navigationParams['q'] = $query;
            $robots = 'noindex, follow';
        }

        if ($hasCatalogFilters) {
            $robots = 'noindex, follow';
            $navigationParams = array_merge($navigationParams, self::catalogParams());
            $params = [];
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        $canonical = $baseUrl . $canonicalPath . ($params !== [] ? '?' . http_build_query($params) : '');
        $image = self::absoluteUrl($data['metaImage'] ?? Banners::imagePath('proletna-promociya'), $baseUrl);
        $imageAlt = trim((string) ($data['metaImageAlt'] ?? $title));
        $type = (string) ($data['ogType'] ?? 'website');
        $previous = $page > 1 ? self::pageUrl($baseUrl . $canonicalPath, $navigationParams, $page - 1) : null;
        $next = $page < $lastPage ? self::pageUrl($baseUrl . $canonicalPath, $navigationParams, $page + 1) : null;

        return [
            'siteName' => $siteName,
            'title' => $title,
            'description' => mb_substr($description, 0, 320),
            'robots' => $robots,
            'canonical' => $canonical,
            'previous' => $previous,
            'next' => $next,
            'type' => $type,
            'image' => $image,
            'imageAlt' => $imageAlt,
            'twitterCard' => $image !== null ? 'summary_large_image' : 'summary',
            'productPrice' => $data['productPrice'] ?? null,
            'productCurrency' => $data['productCurrency'] ?? null,
            'productAvailability' => $data['productAvailability'] ?? null,
            'jsonLd' => self::jsonLd($siteName, $baseUrl, $path),
        ];
    }

    private static function description(string $path, string $siteName): string
    {
        return match (true) {
            $path === '/' => $siteName . ' — внимателно подбрани продукти, нови предложения и удобно онлайн пазаруване.',
            str_starts_with($path, '/catalog') => 'Разгледайте каталога на ' . $siteName . ' и открийте най-новите ни продукти и колекции.',
            default => $siteName . ' — онлайн магазин с подбрани продукти и удобно пазаруване.',
        };
    }

    private static function isPrivate(string $path, int $status): bool
    {
        if ($status >= 400) {
            return true;
        }

        foreach (['/account', '/cart', '/favorites', '/login'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private static function absoluteUrl(mixed $url, string $baseUrl): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        return preg_match('#^https?://#i', $url) === 1 ? $url : $baseUrl . '/' . ltrim($url, '/');
    }

    /** @param array<string, mixed> $params */
    private static function pageUrl(string $base, array $params, int $page): string
    {
        if ($page > 1) {
            $params['page'] = $page;
        }

        return $base . ($params !== [] ? '?' . http_build_query($params) : '');
    }

    private static function hasCatalogFilters(): bool
    {
        foreach (['min_price', 'max_price', 'availability', 'sale', 'sort', 'option'] as $key) {
            $value = \App\Core\Request::query($key);
            if ($value !== null && $value !== '' && $value !== []) return true;
        }
        return false;
    }

    /** @return array<string, mixed> */
    private static function catalogParams(): array
    {
        $params = [];
        foreach (['min_price', 'max_price', 'availability', 'sale', 'sort', 'option'] as $key) {
            $value = \App\Core\Request::query($key);
            if ($value !== null && $value !== '' && $value !== []) $params[$key] = $value;
        }
        return $params;
    }

    /** @return list<array<string, mixed>> */
    private static function jsonLd(string $siteName, string $baseUrl, string $path): array
    {
        if ($path !== '/') {
            return [];
        }

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                '@id' => $baseUrl . '/#organization',
                'name' => $siteName,
                'url' => $baseUrl . '/',
                'email' => (string) ($_ENV['COMPANY_EMAIL'] ?? ''),
                'telephone' => (string) ($_ENV['COMPANY_PHONE'] ?? ''),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                '@id' => $baseUrl . '/#website',
                'name' => $siteName,
                'url' => $baseUrl . '/',
                'publisher' => ['@id' => $baseUrl . '/#organization'],
                'inLanguage' => 'bg-BG',
            ],
        ];
    }
}
