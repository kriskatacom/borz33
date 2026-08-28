<?php

declare(strict_types=1);

use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\HealthController;

/** @var \App\Core\Router $router */

$router->get('/', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

$router->post('/auth/register', [RegisterController::class, 'store']);
$router->post('/auth/verify-email', [EmailVerificationController::class, 'verify']);
$router->post('/auth/verify-email/resend', [EmailVerificationController::class, 'resend']);
