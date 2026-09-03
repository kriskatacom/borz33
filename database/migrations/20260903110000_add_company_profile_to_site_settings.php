<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCompanyProfileToSiteSettings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('site_settings')
            ->addColumn('company_name', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'storefront_indexing_enabled'])
            ->addColumn('company_legal_name', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'company_name'])
            ->addColumn('company_eik', 'string', ['limit' => 32, 'null' => true, 'default' => null, 'after' => 'company_legal_name'])
            ->addColumn('company_vat', 'string', ['limit' => 32, 'null' => true, 'default' => null, 'after' => 'company_eik'])
            ->addColumn('company_mol', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'company_vat'])
            ->addColumn('company_address', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'company_mol'])
            ->addColumn('company_city', 'string', ['limit' => 100, 'null' => true, 'default' => null, 'after' => 'company_address'])
            ->addColumn('company_postal_code', 'string', ['limit' => 20, 'null' => true, 'default' => null, 'after' => 'company_city'])
            ->addColumn('company_country', 'string', ['limit' => 100, 'null' => true, 'default' => null, 'after' => 'company_postal_code'])
            ->addColumn('company_phone', 'string', ['limit' => 64, 'null' => true, 'default' => null, 'after' => 'company_country'])
            ->addColumn('company_email', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'company_phone'])
            ->addColumn('company_website', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'company_email'])
            ->addColumn('company_privacy_url', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'company_website'])
            ->addColumn('company_terms_url', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'company_privacy_url'])
            ->update();
    }
}
