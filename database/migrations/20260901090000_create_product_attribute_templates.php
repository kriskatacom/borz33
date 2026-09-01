<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductAttributeTemplates extends AbstractMigration
{
    public function up(): void
    {
        $this->table('product_attribute_templates')
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('category_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('parameters', 'json', ['null' => true, 'default' => null])
            ->addColumn('options', 'json', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['category_id', 'name'], ['name' => 'product_attribute_templates_category_name_index'])
            ->addForeignKey('category_id', 'categories', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'product_attribute_templates_category_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('product_attribute_templates')->drop()->save();
    }
}
