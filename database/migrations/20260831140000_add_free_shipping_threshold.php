<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFreeShippingThreshold extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('free_shipping_threshold', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false, 'default' => 60.00])
            ->update();
    }

    public function down(): void
    {
        $this->table('site_settings')->removeColumn('free_shipping_threshold')->update();
    }
}
