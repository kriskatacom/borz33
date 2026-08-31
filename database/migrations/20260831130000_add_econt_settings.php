<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEcontSettings extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('econt_environment', 'string', ['limit' => 16, 'null' => false, 'default' => 'demo'])
            ->addColumn('econt_production_username', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('econt_production_password', 'text', ['null' => true, 'default' => null])
            ->addColumn('econt_production_verified_at', 'datetime', ['null' => true, 'default' => null])
            ->update();
    }

    public function down(): void
    {
        $this->table('site_settings')
            ->removeColumn('econt_production_verified_at')
            ->removeColumn('econt_production_password')
            ->removeColumn('econt_production_username')
            ->removeColumn('econt_environment')
            ->update();
    }
}
