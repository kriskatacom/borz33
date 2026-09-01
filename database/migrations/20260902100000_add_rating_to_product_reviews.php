<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRatingToProductReviews extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_reviews')
            ->addColumn('rating', 'integer', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY, 'signed' => false, 'null' => false, 'default' => 5, 'after' => 'user_id'])
            ->addIndex(['product_id', 'rating'], ['name' => 'product_reviews_product_rating_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('product_reviews')
            ->removeIndex(['product_id', 'rating'])
            ->removeColumn('rating')
            ->update();
    }
}
