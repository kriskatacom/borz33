<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/bootstrap/eloquent.php';
require_once dirname(__DIR__, 2) . '/api/app/Core/Autoloader.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

use App\Core\Router;
use Store\Core\StoreAuth;
use Store\Core\View;

StoreAuth::boot();

$router = new Router(View::renderError(...));
require dirname(__DIR__) . '/routes/web.php';
$router->resolve();
