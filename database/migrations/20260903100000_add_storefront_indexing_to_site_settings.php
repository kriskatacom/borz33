<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddStorefrontIndexingToSiteSettings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('site_settings')
            ->addColumn('storefront_indexing_enabled', 'boolean', ['default' => true, 'after' => 'storefront_status'])
            ->update();
    }
}
