<?php

declare(strict_types=1);

use App\Controllers\HealthController;

/** @var \App\Core\Router $router */

$router->get('/', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);
