<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePagesTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('pages')
            ->addColumn('title', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('meta_title', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('meta_description', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'pages_slug_unique'])
            ->addIndex(['is_active'], ['name' => 'pages_is_active_index'])
            ->addIndex(['sort_order'], ['name' => 'pages_sort_order_index'])
            ->create();

        $this->table('page_fields')
            ->addColumn('page_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('field_type', 'string', ['limit' => 32, 'null' => false, 'default' => 'text'])
            ->addColumn('value', 'text', ['null' => true, 'default' => null])
            ->addColumn('media_file_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('is_required', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['page_id', 'slug'], ['unique' => true, 'name' => 'page_fields_page_id_slug_unique'])
            ->addIndex(['page_id', 'sort_order'], ['name' => 'page_fields_page_id_sort_order_index'])
            ->addIndex(['media_file_id'], ['name' => 'page_fields_media_file_id_index'])
            ->addForeignKey('page_id', 'pages', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'page_fields_page_id_foreign',
            ])
            ->addForeignKey('media_file_id', 'media_files', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'page_fields_media_file_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('page_fields')->drop()->save();
        $this->table('pages')->drop()->save();
    }
}
