<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductReviewsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_reviews')
            ->addColumn('product_id', 'integer', ['signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false])
            ->addColumn('body', 'text')
            ->addTimestamps()
            ->addIndex(['product_id', 'created_at'], ['name' => 'product_reviews_product_created_index'])
            ->addIndex(['product_id', 'user_id'], ['unique' => true, 'name' => 'product_reviews_product_user_unique'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('product_reviews')->drop()->save();
    }
}
