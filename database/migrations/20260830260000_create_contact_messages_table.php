<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContactMessagesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('contact_messages')
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('name', 'string', ['limit' => 160])
            ->addColumn('email', 'string', ['limit' => 191])
            ->addColumn('phone', 'string', ['limit' => 40, 'null' => true, 'default' => null])
            ->addColumn('subject', 'string', ['limit' => 191])
            ->addColumn('message', 'text')
            ->addColumn('ip_hash', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('email_sent', 'boolean', ['default' => false])
            ->addColumn('read_at', 'datetime', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['read_at', 'created_at'], ['name' => 'contact_messages_read_created_index'])
            ->addIndex(['email'], ['name' => 'contact_messages_email_index'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('contact_messages')->drop()->save();
    }
}
