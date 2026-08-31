<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddInvoicePaymentSnapshot extends AbstractMigration
{
    public function up(): void
    {
        $this->table('invoices')->addColumn('payment_snapshot', 'json', ['null' => true, 'default' => null, 'after' => 'buyer_snapshot'])->update();
    }

    public function down(): void
    {
        $this->table('invoices')->removeColumn('payment_snapshot')->update();
    }
}
