<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDefaultProductPersonalization extends AbstractMigration
{
    public function up(): void
    {
        $this->table('site_settings')->addColumn('product_personalization_default', 'json', ['null' => true, 'default' => null])->update();
        $this->table('products')->addColumn('personalization_override', 'boolean', ['null' => false, 'default' => false, 'after' => 'personalization_max_length'])->update();
        $this->execute('UPDATE products SET personalization_override = 1');
    }

    public function down(): void
    {
        $this->table('products')->removeColumn('personalization_override')->update();
        $this->table('site_settings')->removeColumn('product_personalization_default')->update();
    }
}
