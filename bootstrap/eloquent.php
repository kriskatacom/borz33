<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

if (defined('ELOQUENT_BOOTED')) {
    return;
}

define('ELOQUENT_BOOTED', true);

$capsule = new Capsule();

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'borz33',
    'username' => getenv('DB_USERNAME') ?: 'borz33',
    'password' => getenv('DB_PASSWORD') ?: 'borz33',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container()));
$capsule->setAsGlobal();
$capsule->bootEloquent();
