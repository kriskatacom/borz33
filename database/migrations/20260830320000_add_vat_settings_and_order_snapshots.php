<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddVatSettingsAndOrderSnapshots extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('vat_enabled', 'boolean', ['null' => false, 'default' => true])
            ->update();

        // Keep the tax rules used at checkout with the order, so historic documents stay correct.
        $this->table('orders')
            ->addColumn('vat_enabled', 'boolean', ['null' => false, 'default' => true, 'after' => 'currency'])
            ->addColumn('vat_rate', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => false, 'default' => 20, 'after' => 'vat_enabled'])
            ->update();
    }

    public function down(): void
    {
        $this->table('orders')->removeColumn('vat_rate')->removeColumn('vat_enabled')->update();
        $this->table('site_settings')->removeColumn('vat_enabled')->update();
    }
}
