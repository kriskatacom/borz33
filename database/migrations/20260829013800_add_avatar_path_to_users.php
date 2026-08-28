<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAvatarPathToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('avatar_path', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'after' => 'phone',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('avatar_path')
            ->update();
    }
}
