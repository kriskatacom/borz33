<?php

declare(strict_types=1);

use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordResetController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\SessionController;
use App\Controllers\HealthController;
use App\Middlewares\Authenticate;

/** @var \App\Core\Router $router */

$router->get('/', [HealthController::class, 'index']);
$router->get('/health', [HealthController::class, 'index']);

$router->post('/auth/register', [RegisterController::class, 'store']);
$router->post('/auth/verify-email', [EmailVerificationController::class, 'verify']);
$router->post('/auth/verify-email/resend', [EmailVerificationController::class, 'resend']);
$router->post('/auth/login', [LoginController::class, 'store']);
$router->post('/auth/login/device', [LoginController::class, 'verifyDevice']);
$router->post('/auth/login/device/resend', [LoginController::class, 'resendDeviceCode']);

$router->post('/auth/admin/login', [LoginController::class, 'storeAdmin']);
$router->post('/auth/admin/login/device', [LoginController::class, 'verifyDeviceAdmin']);
$router->post('/auth/admin/login/device/resend', [LoginController::class, 'resendDeviceCodeAdmin']);
$router->post('/auth/admin/password/forgot', [PasswordResetController::class, 'forgotAdmin']);
$router->post('/auth/admin/password/reset', [PasswordResetController::class, 'resetAdmin']);

$router->get('/auth/me', [SessionController::class, 'show'], [Authenticate::class]);
$router->post('/auth/logout', [SessionController::class, 'destroy'], [Authenticate::class]);
