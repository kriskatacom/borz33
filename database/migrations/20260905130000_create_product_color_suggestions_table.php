<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductColorSuggestionsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_color_suggestions')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_variant_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_image_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('color_name_bg', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('color_hex', 'string', ['limit' => 7, 'null' => false])
            ->addColumn('confidence', 'decimal', ['precision' => 5, 'scale' => 4, 'null' => true, 'default' => null])
            ->addColumn('is_multicolor', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('model', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['product_variant_id', 'created_at'], ['name' => 'product_color_suggestions_variant_created_index'])
            ->addForeignKey('product_id', 'products', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_variant_id', 'product_variants', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('product_image_id', 'product_images', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('product_color_suggestions')->drop()->save();
    }
}
