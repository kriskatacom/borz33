<?php

declare(strict_types=1);

use App\Database\Factories\ProductFactory;
use App\Models\Product;
use Phinx\Seed\AbstractSeed;

final class ProductSeeder extends AbstractSeed
{
    public function run(): void
    {
        require_once dirname(__DIR__) . '/bootstrap.php';

        $count = max(1, (int) (getenv('PRODUCT_SEED_COUNT') ?: 24));

        Product::withTrashed()
            ->where('sku', 'like', 'SEED-%')
            ->forceDelete();

        $active = (int) max(1, round($count * 0.75));
        $inactive = max(2, (int) round($count * 0.15));
        $trashed = max(1, $count - $active - $inactive);

        for ($index = 0; $index < $active; $index++) {
            ProductFactory::new()->create();
        }

        for ($index = 0; $index < $inactive; $index++) {
            ProductFactory::new()->inactive()->create();
        }

        for ($index = 0; $index < $trashed; $index++) {
            ProductFactory::new()->state(['deleted_at' => true])->create();
        }
    }
}
