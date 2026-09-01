<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdminNotificationsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('admin_notifications')
            ->addColumn('type', 'string', ['limit' => 64])
            ->addColumn('level', 'string', ['limit' => 16, 'default' => 'info'])
            ->addColumn('title', 'string', ['limit' => 191])
            ->addColumn('body', 'text')
            ->addColumn('link', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('subject_type', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('subject_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('metadata', 'json', ['null' => true, 'default' => null])
            ->addColumn('read_at', 'datetime', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['read_at', 'created_at'], ['name' => 'admin_notifications_read_created_index'])
            ->addIndex(['type', 'subject_id'], ['name' => 'admin_notifications_type_subject_index'])
            ->create();
    }

    public function down(): void
    {
        $this->table('admin_notifications')->drop()->save();
    }
}
