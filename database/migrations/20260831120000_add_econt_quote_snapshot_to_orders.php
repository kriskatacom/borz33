<?php
declare(strict_types=1);
use Phinx\Migration\AbstractMigration;
final class AddEcontQuoteSnapshotToOrders extends AbstractMigration
{
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('shipping_payer','string',['limit'=>16,'null'=>false,'default'=>'receiver','after'=>'delivery_method'])
            ->addColumn('econt_quote_snapshot','json',['null'=>true,'default'=>null,'after'=>'econt_office_code'])
            ->update();
    }
    public function down(): void
    {
        $this->table('orders')->removeColumn('econt_quote_snapshot')->removeColumn('shipping_payer')->update();
    }
}
