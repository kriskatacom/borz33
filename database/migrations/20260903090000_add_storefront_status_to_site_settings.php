<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddStorefrontStatusToSiteSettings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('site_settings')
            ->addColumn('storefront_status', 'string', ['limit' => 20, 'default' => 'live', 'after' => 'logo_media_file_id'])
            ->update();
    }
}
