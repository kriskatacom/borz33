<?php

declare(strict_types=1);

use Store\Controllers\AccountController;
use Store\Controllers\AuthController;
use Store\Controllers\ContentPageController;
use Store\Controllers\ContactController;
use Store\Controllers\HomeController;
use Store\Controllers\ShopController;

/** @var \App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/catalog', [ShopController::class, 'catalog']);
$router->get('/catalog/{slug}', [ShopController::class, 'catalog']);
$router->get('/products/{slug}', [ShopController::class, 'product']);
$router->post('/products/{slug}/reviews', [ShopController::class, 'storeReview']);
$router->post('/products/{slug}/reviews/{id}', [ShopController::class, 'updateReview']);
$router->get('/products/{slug}/quick-view', [ShopController::class, 'quickView']);
$router->post('/products/{slug}/cart', [ShopController::class, 'addToCart']);
$router->get('/search/products', [ShopController::class, 'search']);
$router->get('/favorites', [ShopController::class, 'favorites']);
$router->post('/favorites/{id}/toggle', [ShopController::class, 'toggleFavorite']);
$router->get('/cart', [ShopController::class, 'cart']);
$router->get('/checkout', [ShopController::class, 'checkout']);
$router->post('/checkout', [ShopController::class, 'placeOrder']);
$router->post('/checkout/shipping-quote', [ShopController::class, 'shippingQuote']);
$router->get('/checkout/success', [ShopController::class, 'checkoutSuccess']);
$router->get('/cart/data', [ShopController::class, 'cartData']);
$router->post('/cart/{index}', [ShopController::class, 'updateCart']);
$router->post('/cart/{index}/delete', [ShopController::class, 'removeCart']);
$router->get('/contact', [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'store']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/login/code', [AuthController::class, 'resendCode']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/register/verify', [AuthController::class, 'verifyEmail']);
$router->post('/register/resend', [AuthController::class, 'resendVerification']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/account', [AccountController::class, 'show']);
$router->get('/account/{section}', [AccountController::class, 'show']);
$router->post('/account/profile', [AccountController::class, 'updateProfile']);
$router->post('/account/password', [AccountController::class, 'updatePassword']);
$router->post('/account/theme', [AccountController::class, 'updateTheme']);
$router->post('/account/avatar', [AccountController::class, 'updateAvatar']);
$router->post('/account/avatar/delete', [AccountController::class, 'destroyAvatar']);
$router->post('/account/messages/{id}/reply', [AccountController::class, 'replyMessage']);
$router->post('/account/addresses/{id}/delete', [AccountController::class, 'destroyAddress']);
$router->post('/account/addresses/{id}/default', [AccountController::class, 'makeDefaultAddress']);
$router->post('/account/addresses/{id}', [AccountController::class, 'updateAddress']);
$router->post('/account/addresses', [AccountController::class, 'storeAddress']);

// Keep the CMS catch-all last so explicit store and account routes always win.
$router->get('/{slug*}', [ContentPageController::class, 'show']);
