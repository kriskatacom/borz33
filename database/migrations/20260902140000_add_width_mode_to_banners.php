<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWidthModeToBanners extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('width_mode', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'container',
                'after' => 'height',
            ])
            ->addIndex(['width_mode'], ['name' => 'banners_width_mode_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('banners')
            ->removeIndexByName('banners_width_mode_index')
            ->removeColumn('width_mode')
            ->update();
    }
}
