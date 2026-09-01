<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AllowMultipleProductReviewsPerUser extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_reviews')
            ->removeIndexByName('product_reviews_product_user_unique')
            ->update();
    }

    public function down(): void
    {
        $this->table('product_reviews')
            ->addIndex(['product_id', 'user_id'], ['unique' => true, 'name' => 'product_reviews_product_user_unique'])
            ->update();
    }
}
