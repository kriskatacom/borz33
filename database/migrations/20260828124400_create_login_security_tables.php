<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginSecurityTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('login_attempts')
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => false])
            ->addColumn('successful', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['email', 'created_at'], ['name' => 'login_attempts_email_created_at_index'])
            ->addIndex(['ip_address', 'created_at'], ['name' => 'login_attempts_ip_created_at_index'])
            ->create();

        $this->table('api_tokens')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_device_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'default' => null])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true, 'default' => null])
            ->addColumn('last_used_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('expires_at', 'timestamp', ['null' => false])
            ->addTimestamps()
            ->addIndex(['token_hash'], ['unique' => true, 'name' => 'api_tokens_token_hash_unique'])
            ->addIndex(['user_id'], ['name' => 'api_tokens_user_id_index'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'api_tokens_user_id_foreign',
            ])
            ->addForeignKey('user_device_id', 'user_devices', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'api_tokens_user_device_id_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('api_tokens')->drop()->save();
        $this->table('login_attempts')->drop()->save();
    }
}
