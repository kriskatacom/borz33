<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('products')
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('sku', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('short_description', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('compare_at_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('personalization_enabled', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('personalization_label', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('personalization_description', 'text', ['null' => true, 'default' => null])
            ->addColumn('personalization_required', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('personalization_max_length', 'integer', ['signed' => false, 'null' => false, 'default' => 120])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'products_slug_unique'])
            ->addIndex(['sku'], ['unique' => true, 'name' => 'products_sku_unique'])
            ->addIndex(['is_active'], ['name' => 'products_is_active_index'])
            ->create();

        $this->table('product_parameters')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('value', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['product_id', 'sort_order'], ['name' => 'product_parameters_product_id_sort_order_index'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_parameters_product_id_foreign',
            ])
            ->create();

        $this->table('product_options')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['product_id', 'slug'], ['unique' => true, 'name' => 'product_options_product_id_slug_unique'])
            ->addIndex(['product_id', 'sort_order'], ['name' => 'product_options_product_id_sort_order_index'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_options_product_id_foreign',
            ])
            ->create();

        $this->table('product_option_values')
            ->addColumn('product_option_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('hex_color', 'string', ['limit' => 7, 'null' => true, 'default' => null])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['product_option_id', 'slug'], ['unique' => true, 'name' => 'product_option_values_option_id_slug_unique'])
            ->addIndex(['product_option_id', 'sort_order'], ['name' => 'product_option_values_option_id_sort_order_index'])
            ->addForeignKey('product_option_id', 'product_options', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_option_values_product_option_id_foreign',
            ])
            ->create();

        $this->table('product_variants')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('sku', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
            ->addColumn('compare_at_price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true, 'default' => null])
            ->addColumn('stock', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['sku'], ['unique' => true, 'name' => 'product_variants_sku_unique'])
            ->addIndex(['product_id', 'is_active'], ['name' => 'product_variants_product_id_is_active_index'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_variants_product_id_foreign',
            ])
            ->create();

        $this->table('product_variant_values')
            ->addColumn('product_variant_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_option_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('product_option_value_id', 'integer', ['signed' => false, 'null' => false])
            ->addTimestamps()
            ->addIndex(['product_variant_id', 'product_option_id'], [
                'unique' => true,
                'name' => 'product_variant_values_variant_option_unique',
            ])
            ->addIndex(['product_variant_id', 'product_option_value_id'], [
                'unique' => true,
                'name' => 'product_variant_values_variant_value_unique',
            ])
            ->addForeignKey('product_variant_id', 'product_variants', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_variant_values_variant_id_foreign',
            ])
            ->addForeignKey('product_option_id', 'product_options', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_variant_values_option_id_foreign',
            ])
            ->addForeignKey('product_option_value_id', 'product_option_values', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_variant_values_value_id_foreign',
            ])
            ->create();

        $this->table('product_personalization_fields')
            ->addColumn('product_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('field_type', 'string', ['limit' => 32, 'null' => false, 'default' => 'text'])
            ->addColumn('is_required', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('max_length', 'integer', ['signed' => false, 'null' => false, 'default' => 120])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['product_id', 'sort_order'], ['name' => 'product_personalization_fields_product_sort_index'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'product_personalization_fields_product_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('product_personalization_fields')->drop()->save();
        $this->table('product_variant_values')->drop()->save();
        $this->table('product_variants')->drop()->save();
        $this->table('product_option_values')->drop()->save();
        $this->table('product_options')->drop()->save();
        $this->table('product_parameters')->drop()->save();
        $this->table('products')->drop()->save();
    }
}
