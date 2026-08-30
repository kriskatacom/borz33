<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTrackingToOrders extends AbstractMigration
{
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('tracking_number', 'string', ['limit' => 64, 'null' => true, 'default' => null, 'after' => 'econt_office_code'])
            ->addColumn('shipped_at', 'datetime', ['null' => true, 'default' => null, 'after' => 'tracking_number'])
            ->addIndex(['tracking_number'], ['name' => 'orders_tracking_number_index'])
            ->update();
    }

    public function down(): void
    {
        $this->table('orders')->removeIndexByName('orders_tracking_number_index')->removeColumn('shipped_at')->removeColumn('tracking_number')->update();
    }
}
