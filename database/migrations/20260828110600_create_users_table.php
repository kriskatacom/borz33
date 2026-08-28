<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('first_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('last_name', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('email_verified_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('password', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('phone', 'string', ['limit' => 32, 'null' => true, 'default' => null])
            ->addColumn('role', 'string', ['limit' => 32, 'null' => false, 'default' => 'customer'])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('remember_token', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('last_login_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('last_login_ip', 'string', ['limit' => 45, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex(['email'], ['unique' => true, 'name' => 'users_email_unique'])
            ->addIndex(['role'], ['name' => 'users_role_index'])
            ->addIndex(['is_active'], ['name' => 'users_is_active_index'])
            ->create();

        $this->table('password_reset_tokens', [
            'id' => false,
            'primary_key' => ['email'],
        ])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
            ->create();

        $this->table('email_verification_tokens', [
            'id' => false,
            'primary_key' => ['email'],
        ])
            ->addColumn('email', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('token', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
            ->create();
    }

    public function down(): void
    {
        $this->table('email_verification_tokens')->drop()->save();
        $this->table('password_reset_tokens')->drop()->save();
        $this->table('users')->drop()->save();
    }
}
