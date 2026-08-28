<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMediaFilesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('media_files')
            ->addColumn('path', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('original_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mime', 'string', ['limit' => 127, 'null' => false])
            ->addColumn('extension', 'string', ['limit' => 32, 'null' => false, 'default' => ''])
            ->addColumn('kind', 'string', ['limit' => 16, 'null' => false, 'default' => 'other'])
            ->addColumn('size', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('alt', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('uploaded_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['path'], ['unique' => true, 'name' => 'media_files_path_unique'])
            ->addIndex(['kind'], ['name' => 'media_files_kind_index'])
            ->addIndex(['original_name'], ['name' => 'media_files_original_name_index'])
            ->addForeignKey('uploaded_by', 'users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'media_files_uploaded_by_foreign',
            ])
            ->create();
    }

    public function down(): void
    {
        $this->table('media_files')->drop()->save();
    }
}
