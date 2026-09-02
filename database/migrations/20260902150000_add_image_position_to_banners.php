<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImagePositionToBanners extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('image_position', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'center',
                'after' => 'width_mode',
            ])
            ->addIndex(['image_position'], ['name' => 'banners_image_position_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('banners')
            ->removeIndexByName('banners_image_position_index')
            ->removeColumn('image_position')
            ->update();
    }
}
