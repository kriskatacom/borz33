<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserDeviceLoginTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_devices')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('device_name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true, 'default' => null])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'default' => null])
            ->addColumn('is_trusted', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('trusted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('last_seen_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addTimestamps()
            ->addIndex(['user_id', 'device_uuid'], ['unique' => true, 'name' => 'user_devices_user_id_device_uuid_unique'])
            ->addIndex(['user_id', 'is_trusted'], ['name' => 'user_devices_user_id_is_trusted_index'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'user_devices_user_id_foreign',
            ])
            ->create();

        $this->table('device_login_codes')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('device_uuid', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('device_name', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true, 'default' => null])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'default' => null])
            ->addColumn('code_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addColumn('verified_at', 'timestamp', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['user_id', 'device_uuid'], ['name' => 'device_login_codes_user_id_device_uuid_index'])
            ->addIndex(['expires_at'], ['name' => 'device_login_codes_expires_at_index'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'device_login_codes_user_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('device_login_codes')->drop()->save();
        $this->table('user_devices')->drop()->save();
    }
}
