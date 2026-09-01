<?php

declare(strict_types=1);

use PhinxMigrationAbstractMigration;

final class AddAdminBackgroundOverlayToSiteSettings extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')->addColumn('admin_background_overlay', 'integer', ['default' => 48, 'signed' => false])->update();
    }

    public function down(): void
    {
        $this->table('site_settings')->removeColumn('admin_background_overlay')->update();
    }
}
