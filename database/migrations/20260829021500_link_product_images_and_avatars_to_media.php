<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LinkProductImagesAndAvatarsToMedia extends AbstractMigration
{
    public function up(): void
    {
        $images = $this->table('product_images');
        $images
            ->addColumn('media_file_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'product_variant_id',
            ])
            ->addIndex(['media_file_id'], ['name' => 'product_images_media_file_id_index'])
            ->addForeignKey('media_file_id', 'media_files', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'product_images_media_file_id_foreign',
            ])
            ->removeIndexByName('product_images_path_unique')
            ->update();

        $this->table('users')
            ->addColumn('avatar_media_id', 'integer', [
                'signed' => false,
                'null' => true,
                'default' => null,
                'after' => 'avatar_path',
            ])
            ->addIndex(['avatar_media_id'], ['name' => 'users_avatar_media_id_index'])
            ->addForeignKey('avatar_media_id', 'media_files', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
                'constraint' => 'users_avatar_media_id_foreign',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('users')
            ->dropForeignKey('avatar_media_id')
            ->removeIndexByName('users_avatar_media_id_index')
            ->removeColumn('avatar_media_id')
            ->update();

        $images = $this->table('product_images');
        $images
            ->dropForeignKey('media_file_id')
            ->removeIndexByName('product_images_media_file_id_index')
            ->removeColumn('media_file_id')
            ->addIndex(['path'], ['unique' => true, 'name' => 'product_images_path_unique'])
            ->update();
    }
}
