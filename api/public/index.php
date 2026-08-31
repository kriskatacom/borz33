<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/bootstrap/eloquent.php';
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

use App\Core\Cors;
use App\Core\Router;

Cors::handle();

$router = new Router();
require dirname(__DIR__) . '/routes/api.php';
$router->resolve();
