<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSenderToContactMessageReplies extends AbstractMigration
{
    public function up(): void
    {
        $this->table('contact_message_replies')
            ->addColumn('sender_type', 'string', ['limit' => 20, 'default' => 'admin', 'after' => 'admin_user_id'])
            ->addColumn('sender_user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'after' => 'sender_type'])
            ->addIndex(['sender_user_id'], ['name' => 'contact_replies_sender_user_index'])
            ->addForeignKey('sender_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->update();
    }

    public function down(): void
    {
        $this->table('contact_message_replies')->dropForeignKey('sender_user_id')->removeIndexByName('contact_replies_sender_user_index')->removeColumn('sender_user_id')->removeColumn('sender_type')->update();
    }
}
