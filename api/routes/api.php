<?php

declare(strict_types=1);

use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\ProductImagesController;
use App\Controllers\Admin\ProductsController;
use App\Controllers\Admin\UsersController;
use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordResetController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\SessionController;
use App\Controllers\HealthController;
use App\Middlewares\Authenticate;
use App\Middlewares\RequireAdmin;

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

$admin = [Authenticate::class, RequireAdmin::class];
$router->get('/admin/users', [UsersController::class, 'index'], $admin);
$router->post('/admin/users', [UsersController::class, 'store'], $admin);
$router->get('/admin/users/{id}', [UsersController::class, 'show'], $admin);
$router->put('/admin/users/{id}', [UsersController::class, 'update'], $admin);
$router->patch('/admin/users/{id}', [UsersController::class, 'update'], $admin);
$router->delete('/admin/users/{id}', [UsersController::class, 'destroy'], $admin);
$router->post('/admin/users/{id}/restore', [UsersController::class, 'restore'], $admin);
$router->post('/admin/users/{id}/avatar', [UsersController::class, 'storeAvatar'], $admin);
$router->delete('/admin/users/{id}/avatar', [UsersController::class, 'destroyAvatar'], $admin);

$router->get('/admin/media', [MediaController::class, 'index'], $admin);
$router->post('/admin/media', [MediaController::class, 'store'], $admin);
$router->get('/admin/media/{id}', [MediaController::class, 'show'], $admin);
$router->patch('/admin/media/{id}', [MediaController::class, 'update'], $admin);
$router->delete('/admin/media/{id}', [MediaController::class, 'destroy'], $admin);

$router->post('/admin/products', [ProductsController::class, 'store'], $admin);
$router->get('/admin/products', [ProductsController::class, 'index'], $admin);
$router->get('/admin/products/{id}', [ProductsController::class, 'show'], $admin);
$router->put('/admin/products/{id}', [ProductsController::class, 'update'], $admin);
$router->patch('/admin/products/{id}', [ProductsController::class, 'update'], $admin);
$router->delete('/admin/products/{id}', [ProductsController::class, 'destroy'], $admin);
$router->post('/admin/products/{id}/restore', [ProductsController::class, 'restore'], $admin);
$router->post('/admin/products/{id}/personalization/share', [ProductsController::class, 'sharePersonalization'], $admin);
$router->post('/admin/products/{id}/images/front', [ProductImagesController::class, 'storeFront'], $admin);
$router->post('/admin/products/{id}/images', [ProductImagesController::class, 'storeGallery'], $admin);
$router->post('/admin/products/{id}/variants/{variantId}/image', [ProductImagesController::class, 'storeVariant'], $admin);
$router->delete('/admin/products/{id}/variants/{variantId}/image', [ProductImagesController::class, 'destroyVariant'], $admin);
$router->post('/admin/products/{id}/images/{imageId}/front', [ProductImagesController::class, 'makeFront'], $admin);
$router->patch('/admin/products/{id}/images/{imageId}', [ProductImagesController::class, 'update'], $admin);
$router->delete('/admin/products/{id}/images/{imageId}', [ProductImagesController::class, 'destroy'], $admin);
