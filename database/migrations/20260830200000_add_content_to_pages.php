<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddContentToPages extends AbstractMigration
{
    public function up(): void
    {
        $this->table('pages')
            ->addColumn('content', 'text', ['null' => true, 'default' => null, 'after' => 'sort_order'])
            ->update();
    }

    public function down(): void
    {
        $this->table('pages')->removeColumn('content')->update();
    }
}
