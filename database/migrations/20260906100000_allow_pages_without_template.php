<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AllowPagesWithoutTemplate extends AbstractMigration
{
    public function up(): void
    {
        $this->table('pages')
            ->changeColumn('page_template_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->update();
    }

    public function down(): void
    {
        $default = $this->fetchRow("SELECT id FROM page_templates WHERE is_default = 1 ORDER BY id LIMIT 1");
        $defaultId = (int) ($default['id'] ?? 0);

        if ($defaultId < 1) {
            throw new RuntimeException('Cannot restore required page templates without a default template.');
        }

        $this->execute('UPDATE pages SET page_template_id = ' . $defaultId . ' WHERE page_template_id IS NULL');
        $this->table('pages')
            ->changeColumn('page_template_id', 'integer', ['signed' => false, 'null' => false])
            ->update();
    }
}
