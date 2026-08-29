<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLayoutToBanners extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('layout', 'string', [
                'limit' => 32,
                'null' => false,
                'default' => 'split',
                'after' => 'text',
            ])
            ->addIndex(['layout'], ['name' => 'banners_layout_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('banners')
            ->removeIndexByName('banners_layout_index')
            ->removeColumn('layout')
            ->update();
    }
}
