<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrdersTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('number', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 32, 'null' => false, 'default' => 'pending'])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'default' => 'EUR'])
            ->addColumn('subtotal', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('total', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('first_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('last_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('phone', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('delivery_method', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('address_line', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('city', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('postal_code', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('country', 'string', ['limit' => 80, 'null' => false, 'default' => 'България'])
            ->addColumn('payment_method', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('notes', 'text', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['number'], ['unique' => true, 'name' => 'orders_number_unique'])
            ->addIndex(['user_id', 'created_at'], ['name' => 'orders_user_created_at_index'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        $this->table('order_items')
            ->addColumn('order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('variant_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('sku', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('options', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('notes', 'text', ['null' => true, 'default' => null])
            ->addColumn('qty', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('unit_price', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('total', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addTimestamps()
            ->addIndex(['order_id'], ['name' => 'order_items_order_id_index'])
            ->addForeignKey('order_id', 'orders', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->addForeignKey('variant_id', 'product_variants', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('order_items')->drop()->save();
        $this->table('orders')->drop()->save();
    }
}
