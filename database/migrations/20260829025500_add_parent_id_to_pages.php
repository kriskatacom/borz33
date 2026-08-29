<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddParentIdToPages extends AbstractMigration
{
    public function up(): void
    {
        $this->table('pages')
            ->addColumn('parent_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'slug',
            ])
            ->addIndex(['parent_id'], ['name' => 'pages_parent_id_index'])
            ->addForeignKey('parent_id', 'pages', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'pages_parent_id_foreign',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('pages')
            ->dropForeignKey('parent_id')
            ->removeIndexByName('pages_parent_id_index')
            ->removeColumn('parent_id')
            ->update();
    }
}
