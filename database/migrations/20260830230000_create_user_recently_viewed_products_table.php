<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserRecentlyViewedProductsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_recently_viewed_products', ['id' => false, 'primary_key' => ['user_id', 'product_id']])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('viewed_at', 'datetime', ['null' => false])
            ->addIndex(['user_id', 'viewed_at'], ['name' => 'recently_viewed_user_date_index'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('user_recently_viewed_products')->drop()->save();
    }
}
