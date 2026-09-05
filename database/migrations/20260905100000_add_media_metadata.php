<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMediaMetadata extends AbstractMigration
{
    public function change(): void
    {
        $this->table('media_files')
            ->addColumn('title', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'alt'])
            ->addColumn('width', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'after' => 'size'])
            ->addColumn('height', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'after' => 'width'])
            ->update();
    }
}
