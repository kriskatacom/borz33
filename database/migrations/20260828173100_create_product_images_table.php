<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductImagesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_images')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('role', 'string', ['limit' => 16, 'null' => false, 'default' => 'gallery'])
            ->addColumn('path', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('original_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mime', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('size', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('alt', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['product_id', 'role'], ['name' => 'product_images_product_id_role_index'])
            ->addIndex(['path'], ['unique' => true, 'name' => 'product_images_path_unique'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_images_product_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('product_images')->drop()->save();
    }
}
