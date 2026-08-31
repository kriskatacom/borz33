<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEcontOperationsEnabled extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('econt_operations_enabled', 'boolean', ['null' => false, 'default' => true, 'after' => 'free_shipping_threshold'])
            ->update();
    }

    public function down(): void
    {
        $this->table('site_settings')->removeColumn('econt_operations_enabled')->update();
    }
}
