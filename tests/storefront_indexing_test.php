<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap/eloquent.php';
require dirname(__DIR__) . '/api/app/Core/Autoloader.php';
require dirname(__DIR__) . '/web/app/Core/Autoloader.php';

use App\Models\SiteSetting;
use Illuminate\Database\Capsule\Manager as DB;
use Store\Core\Seo;

$settings = SiteSetting::query()->firstOrCreate([]);
$previous = $settings->storefront_indexing_enabled;
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

try {
    DB::connection()->transaction(function () use ($settings, $assert): void {
        $settings->storefront_indexing_enabled = false;
        $settings->save();
        $private = Seo::build(['currentPath' => '/']);
        $assert($private['robots'] === 'noindex, nofollow', 'Изключената индексация не задава noindex, nofollow.');

        $settings->storefront_indexing_enabled = true;
        $settings->save();
        $public = Seo::build(['currentPath' => '/']);
        $assert(str_starts_with($public['robots'], 'index, follow'), 'Включената индексация не задава index, follow.');

        $account = Seo::build(['currentPath' => '/account/orders']);
        $assert($account['robots'] === 'noindex, nofollow', 'Личните страници не са защитени от индексиране.');
    });
    echo "Storefront indexing test passed.\n";
} finally {
    $settings->storefront_indexing_enabled = $previous;
    $settings->save();
}
