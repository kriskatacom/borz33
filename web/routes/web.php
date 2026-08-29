<?php

declare(strict_types=1);

use Store\Controllers\AuthController;
use Store\Controllers\HomeController;
use Store\Controllers\ShopController;

/** @var \App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/catalog', [ShopController::class, 'catalog']);
$router->get('/catalog/{slug}', [ShopController::class, 'catalog']);
$router->get('/favorites', [ShopController::class, 'favorites']);
$router->get('/cart', [ShopController::class, 'cart']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/login/code', [AuthController::class, 'resendCode']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/register/verify', [AuthController::class, 'verifyEmail']);
$router->post('/register/resend', [AuthController::class, 'resendVerification']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/account', [AuthController::class, 'showProfile']);
$router->post('/account/theme', [AuthController::class, 'updateTheme']);
