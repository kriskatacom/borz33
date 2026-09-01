<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddArchivedAtToAdminNotifications extends AbstractMigration
{
    public function up(): void { $this->table('admin_notifications')->addColumn('archived_at', 'datetime', ['null' => true, 'default' => null])->update(); }
    public function down(): void { $this->table('admin_notifications')->removeColumn('archived_at')->update(); }
}
