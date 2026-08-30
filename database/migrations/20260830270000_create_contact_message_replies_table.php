<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContactMessageRepliesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('contact_message_replies')
            ->addColumn('contact_message_id', 'integer', ['signed' => false])
            ->addColumn('admin_user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('body', 'text')
            ->addColumn('email_sent', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addIndex(['contact_message_id', 'created_at'], ['name' => 'contact_replies_message_created_index'])
            ->addForeignKey('contact_message_id', 'contact_messages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('admin_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void { $this->table('contact_message_replies')->drop()->save(); }
}
