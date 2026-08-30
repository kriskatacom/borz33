<?php

declare(strict_types=1);

use App\Controllers\Admin\BannersController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\CategoriesController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\PagesController;
use App\Controllers\Admin\ProductImagesController;
use App\Controllers\Admin\ProductsController;
use App\Controllers\Admin\SettingsController;
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
$router->get('/admin/dashboard', [DashboardController::class, 'show'], $admin);
$router->get('/admin/settings', [SettingsController::class, 'show'], $admin);
$router->patch('/admin/settings', [SettingsController::class, 'update'], $admin);
$router->get('/admin/users', [UsersController::class, 'index'], $admin);
$router->post('/admin/users', [UsersController::class, 'store'], $admin);
$router->get('/admin/users/avatar-presets', [UsersController::class, 'avatarPresets'], $admin);
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

$router->get('/admin/pages', [PagesController::class, 'index'], $admin);
$router->post('/admin/pages', [PagesController::class, 'store'], $admin);
$router->get('/admin/pages/tree', [PagesController::class, 'tree'], $admin);
$router->get('/admin/pages/templates', [PagesController::class, 'templates'], $admin);
$router->get('/admin/pages/{id}', [PagesController::class, 'show'], $admin);
$router->put('/admin/pages/{id}', [PagesController::class, 'update'], $admin);
$router->patch('/admin/pages/{id}', [PagesController::class, 'update'], $admin);
$router->delete('/admin/pages/{id}', [PagesController::class, 'destroy'], $admin);
$router->post('/admin/pages/{id}/restore', [PagesController::class, 'restore'], $admin);

$router->get('/admin/banners', [BannersController::class, 'index'], $admin);
$router->post('/admin/banners', [BannersController::class, 'store'], $admin);
$router->get('/admin/banners/{id}', [BannersController::class, 'show'], $admin);
$router->put('/admin/banners/{id}', [BannersController::class, 'update'], $admin);
$router->patch('/admin/banners/{id}', [BannersController::class, 'update'], $admin);
$router->delete('/admin/banners/{id}', [BannersController::class, 'destroy'], $admin);
$router->post('/admin/banners/{id}/restore', [BannersController::class, 'restore'], $admin);

$router->get('/admin/categories', [CategoriesController::class, 'index'], $admin);
$router->post('/admin/categories', [CategoriesController::class, 'store'], $admin);
$router->get('/admin/categories/tree', [CategoriesController::class, 'tree'], $admin);
$router->get('/admin/categories/{id}', [CategoriesController::class, 'show'], $admin);
$router->put('/admin/categories/{id}', [CategoriesController::class, 'update'], $admin);
$router->patch('/admin/categories/{id}', [CategoriesController::class, 'update'], $admin);
$router->delete('/admin/categories/{id}', [CategoriesController::class, 'destroy'], $admin);
$router->post('/admin/categories/{id}/restore', [CategoriesController::class, 'restore'], $admin);

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
