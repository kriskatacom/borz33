<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserAddressesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_addresses')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 32, 'null' => false, 'default' => 'billing'])
            ->addColumn('party', 'string', ['limit' => 32, 'null' => false, 'default' => 'person'])
            ->addColumn('label', 'string', ['limit' => 80, 'null' => true, 'default' => null])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('first_name', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('last_name', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('company_name', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('eik', 'string', ['limit' => 13, 'null' => true, 'default' => null])
            ->addColumn('vat_number', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('mol', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('line1', 'string', ['limit' => 191, 'null' => false])
            ->addColumn('city', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('postal_code', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('country', 'string', ['limit' => 80, 'null' => false, 'default' => 'България'])
            ->addTimestamps()
            ->addIndex(['user_id', 'type'], ['name' => 'user_addresses_user_id_type_index'])
            ->addIndex(['user_id', 'type', 'is_default'], ['name' => 'user_addresses_user_id_type_default_index'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'user_addresses_user_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('user_addresses')->drop()->save();
    }
}
