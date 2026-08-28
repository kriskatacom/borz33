<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductVariantIdToProductImages extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_images')
            ->addColumn('product_variant_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'product_id',
            ])
            ->addIndex(['product_variant_id'], [
                'unique' => true,
                'name' => 'product_images_product_variant_id_unique',
            ])
            ->addForeignKey('product_variant_id', 'product_variants', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_images_product_variant_id_foreign',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('product_images')
            ->dropForeignKey('product_variant_id')
            ->removeIndexByName('product_images_product_variant_id_unique')
            ->removeColumn('product_variant_id')
            ->update();
    }
}
