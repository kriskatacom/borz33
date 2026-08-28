<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

use App\Core\Router;

$router = new Router();
require dirname(__DIR__) . '/routes/api.php';
$router->resolve();
