<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePageTemplates extends AbstractMigration
{
    public function up(): void
    {
        $this->table('page_templates')
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('view', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => false])
            ->addTimestamps()
            ->addIndex(['slug'], ['unique' => true, 'name' => 'page_templates_slug_unique'])
            ->addIndex(['is_default'], ['name' => 'page_templates_is_default_index'])
            ->create();

        $this->table('page_templates')->insert([
            'name' => 'По подразбиране',
            'slug' => 'default',
            'view' => 'page-templates/default',
            'is_default' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ])->saveData();

        $default = $this->fetchRow("SELECT id FROM page_templates WHERE slug = 'default'");
        $defaultId = (int) ($default['id'] ?? 0);

        $this->table('pages')->addColumn('page_template_id', 'integer', [
            'signed' => false,
            'null' => true,
            'default' => null,
            'after' => 'parent_id',
        ])->update();
        $this->execute('UPDATE pages SET page_template_id = ' . $defaultId . ' WHERE page_template_id IS NULL');
        $this->table('pages')
            ->changeColumn('page_template_id', 'integer', ['signed' => false, 'null' => false])
            ->addIndex(['page_template_id'], ['name' => 'pages_page_template_id_index'])
            ->addForeignKey('page_template_id', 'page_templates', 'id', [
                'delete' => 'RESTRICT', 'update' => 'CASCADE', 'constraint' => 'pages_page_template_id_foreign',
            ])->update();
    }

    public function down(): void
    {
        $this->table('pages')->dropForeignKey('page_template_id')->removeIndexByName('pages_page_template_id_index')->removeColumn('page_template_id')->update();
        $this->table('page_templates')->drop()->save();
    }
}
