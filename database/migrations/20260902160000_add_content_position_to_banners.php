<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddContentPositionToBanners extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('content_position', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'center',
                'after' => 'image_position',
            ])
            ->addIndex(['content_position'], ['name' => 'banners_content_position_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('banners')
            ->removeIndexByName('banners_content_position_index')
            ->removeColumn('content_position')
            ->update();
    }
}
