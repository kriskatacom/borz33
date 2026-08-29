<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCategoriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('categories')
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('parent_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('media_file_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'categories_slug_unique'])
            ->addIndex(['parent_id'], ['name' => 'categories_parent_id_index'])
            ->addIndex(['media_file_id'], ['name' => 'categories_media_file_id_index'])
            ->addIndex(['is_active'], ['name' => 'categories_is_active_index'])
            ->addIndex(['sort_order'], ['name' => 'categories_sort_order_index'])
            ->addForeignKey('parent_id', 'categories', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'categories_parent_id_foreign',
            ])
            ->addForeignKey('media_file_id', 'media_files', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'categories_media_file_id_foreign',
            ])
            ->create();

        $this->table('products')
            ->addColumn('category_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'slug',
            ])
            ->addIndex(['category_id'], ['name' => 'products_category_id_index'])
            ->addForeignKey('category_id', 'categories', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'products_category_id_foreign',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('products')
            ->dropForeignKey('category_id')
            ->removeIndexByName('products_category_id_index')
            ->removeColumn('category_id')
            ->update();

        $this->table('categories')->drop()->save();
    }
}
