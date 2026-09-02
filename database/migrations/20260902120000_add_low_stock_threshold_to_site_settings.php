<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLowStockThresholdToSiteSettings extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('low_stock_threshold', 'integer', ['signed' => false, 'null' => false, 'default' => 5, 'after' => 'free_shipping_threshold'])
            ->update();
    }

    public function down(): void
    {
        $this->table('site_settings')->removeColumn('low_stock_threshold')->update();
    }
}
