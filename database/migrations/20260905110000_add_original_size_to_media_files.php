<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddOriginalSizeToMediaFiles extends AbstractMigration
{
    public function change(): void
    {
        $this->table('media_files')
            ->addColumn('original_size', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'after' => 'size'])
            ->update();
    }
}
