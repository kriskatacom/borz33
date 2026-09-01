<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAdminBackgroundToSiteSettings extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')->addColumn('admin_background', 'string', ['null' => true, 'default' => null, 'limit' => 191])->update();
    }

    public function down(): void
    {
        $this->table('site_settings')->removeColumn('admin_background')->update();
    }
}
