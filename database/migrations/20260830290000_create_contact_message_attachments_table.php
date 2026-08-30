<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateContactMessageAttachmentsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('contact_message_attachments')
            ->addColumn('contact_message_id', 'integer', ['signed' => false])
            ->addColumn('contact_message_reply_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('media_file_id', 'integer', ['signed' => false])
            ->addTimestamps()
            ->addIndex(['contact_message_id'], ['name' => 'contact_attachments_message_index'])
            ->addIndex(['contact_message_reply_id'], ['name' => 'contact_attachments_reply_index'])
            ->addForeignKey('contact_message_id', 'contact_messages', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('contact_message_reply_id', 'contact_message_replies', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('media_file_id', 'media_files', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void { $this->table('contact_message_attachments')->drop()->save(); }
}
