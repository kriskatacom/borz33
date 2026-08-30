<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEcontShippingFields extends AbstractMigration
{
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('shipping_amount', 'decimal', [
                'precision' => 12,
                'scale' => 2,
                'null' => false,
                'default' => 0,
                'after' => 'subtotal',
            ])
            ->addColumn('econt_office_code', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'after' => 'delivery_method',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('orders')
            ->removeColumn('econt_office_code')
            ->removeColumn('shipping_amount')
            ->update();
    }
}
