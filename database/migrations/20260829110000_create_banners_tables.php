<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBannersTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('title', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('text', 'text', ['null' => false])
            ->addColumn('media_file_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'banners_slug_unique'])
            ->addIndex(['media_file_id'], ['name' => 'banners_media_file_id_index'])
            ->addIndex(['is_active'], ['name' => 'banners_is_active_index'])
            ->addIndex(['sort_order'], ['name' => 'banners_sort_order_index'])
            ->addForeignKey('media_file_id', 'media_files', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'banners_media_file_id_foreign',
            ])
            ->create();

        $this->table('banner_buttons')
            ->addColumn('banner_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('label', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('url', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('open_in_new_tab', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('sort_order', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['banner_id', 'sort_order'], ['name' => 'banner_buttons_banner_id_sort_order_index'])
            ->addForeignKey('banner_id', 'banners', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'banner_buttons_banner_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('banner_buttons')->drop()->save();
        $this->table('banners')->drop()->save();
    }
}
