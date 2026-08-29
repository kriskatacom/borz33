<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddThemeToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')
            ->addColumn('theme', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'system',
                'after' => 'is_active',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->removeColumn('theme')
            ->update();
    }
}
