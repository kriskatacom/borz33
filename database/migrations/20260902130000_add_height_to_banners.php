<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHeightToBanners extends AbstractMigration
{
    public function up(): void
    {
        $this->table('banners')
            ->addColumn('height', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'layout',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('banners')
            ->removeColumn('height')
            ->update();
    }
}
