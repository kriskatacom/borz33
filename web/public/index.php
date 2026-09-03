<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/bootstrap/eloquent.php';
require_once dirname(__DIR__, 2) . '/api/app/Core/Autoloader.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

use App\Core\Router;
use App\Models\SiteSetting;
use App\Services\SitemapService;
use Store\Core\StoreAuth;
use Store\Core\View;

if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/sitemap.xml') {
    (new SitemapService())->renderStored();
}

if (SiteSetting::query()->value('storefront_status') === 'development') {
    http_response_code(503);
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    require dirname(__DIR__) . '/views/maintenance.php';
    exit;
}

StoreAuth::boot();

$router = new Router(View::renderError(...));
require dirname(__DIR__) . '/routes/web.php';
$router->resolve();
