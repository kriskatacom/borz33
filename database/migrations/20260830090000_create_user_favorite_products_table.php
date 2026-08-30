<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserFavoriteProductsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_favorite_products', ['id' => false, 'primary_key' => ['user_id', 'product_id']])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('user_favorite_products')->drop()->save();
    }
}
