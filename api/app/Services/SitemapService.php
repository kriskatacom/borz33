<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

final class SitemapService
{
    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/sitemap.xml';
    }

    /** @return array{url:string, generated:bool, generated_at:string|null, counts:array{pages:int,categories:int,products:int}} */
    public function status(): array
    {
        $file = self::path();

        return [
            'url' => rtrim((string) (getenv('WEB_PUBLIC_URL') ?: 'http://localhost:4000'), '/') . '/sitemap.xml',
            'generated' => is_file($file),
            'generated_at' => is_file($file) ? date(DATE_ATOM, (int) filemtime($file)) : null,
            'counts' => $this->counts(),
        ];
    }

    /** @return array{url:string, generated:bool, generated_at:string, counts:array{pages:int,categories:int,products:int}} */
    public function generate(): array
    {
        $file = self::path();
        $xml = $this->xml();

        if (file_put_contents($file, $xml, LOCK_EX) === false) {
            throw new \RuntimeException('Картата на сайта не можа да бъде записана.');
        }

        return [
            'url' => rtrim((string) (getenv('WEB_PUBLIC_URL') ?: 'http://localhost:4000'), '/') . '/sitemap.xml',
            'generated' => true,
            'generated_at' => date(DATE_ATOM, (int) filemtime($file)),
            'counts' => $this->counts(),
        ];
    }

    public function renderStored(): never
    {
        $file = self::path();
        header('Content-Type: application/xml; charset=utf-8');

        if (!is_file($file)) {
            http_response_code(404);
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Картата на сайта още не е генерирана.</error>';
            exit;
        }

        header('Cache-Control: public, max-age=3600');
        readfile($file);
        exit;
    }

    /** @return array{pages:int,categories:int,products:int} */
    private function counts(): array
    {
        return [
            'pages' => Page::query()->where('is_active', true)->count(),
            'categories' => Category::query()->where('is_active', true)->count(),
            'products' => Product::query()->where('is_active', true)->count(),
        ];
    }

    private function xml(): string
    {
        $origin = rtrim((string) (getenv('WEB_PUBLIC_URL') ?: 'http://localhost:4000'), '/');
        $urls = [
            ['loc' => $origin . '/', 'lastmod' => null],
            ['loc' => $origin . '/catalog', 'lastmod' => null],
        ];

        Page::query()->where('is_active', true)->orderBy('id')->get(['slug', 'updated_at'])->each(static function (Page $page) use (&$urls, $origin): void {
            $urls[] = ['loc' => $origin . '/' . ltrim((string) $page->slug, '/'), 'lastmod' => $page->updated_at];
        });
        Category::query()->where('is_active', true)->orderBy('id')->get(['slug', 'updated_at'])->each(static function (Category $category) use (&$urls, $origin): void {
            $urls[] = ['loc' => $origin . '/catalog/' . rawurlencode((string) $category->slug), 'lastmod' => $category->updated_at];
        });
        Product::query()->where('is_active', true)->orderBy('id')->get(['slug', 'updated_at'])->each(static function (Product $product) use (&$urls, $origin): void {
            $urls[] = ['loc' => $origin . '/products/' . rawurlencode((string) $product->slug), 'lastmod' => $product->updated_at];
        });

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</loc>';
            if ($url['lastmod'] !== null) $xml[] = '    <lastmod>' . htmlspecialchars($url['lastmod']->toAtomString(), ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</lastmod>';
            $xml[] = '  </url>';
        }
        $xml[] = '</urlset>';

        return implode("\n", $xml);
    }
}
