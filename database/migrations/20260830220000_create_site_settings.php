<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSiteSettings extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')
            ->addColumn('logo_media_file_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['logo_media_file_id'], ['name' => 'site_settings_logo_media_file_id_index'])
            ->addForeignKey('logo_media_file_id', 'media_files', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'site_settings_logo_media_file_id_foreign',
            ])
            ->create();

        $this->table('site_settings')->insert([
            'logo_media_file_id' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ])->saveData();
    }

    public function down(): void
    {
        $this->table('site_settings')->drop()->save();
    }
}
