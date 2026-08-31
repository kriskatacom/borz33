<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInvoicesTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('orders')
            ->addColumn('invoice_requested', 'boolean', ['default' => false, 'null' => false, 'after' => 'payment_method'])
            ->addColumn('invoice_company', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'invoice_requested'])
            ->addColumn('invoice_eik', 'string', ['limit' => 16, 'null' => true, 'default' => null, 'after' => 'invoice_company'])
            ->addColumn('invoice_vat_number', 'string', ['limit' => 20, 'null' => true, 'default' => null, 'after' => 'invoice_eik'])
            ->addColumn('invoice_address', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'after' => 'invoice_vat_number'])
            ->addColumn('invoice_mol', 'string', ['limit' => 191, 'null' => true, 'default' => null, 'after' => 'invoice_address'])
            ->update();

        $this->table('invoice_sequences', ['id' => false, 'primary_key' => ['name']])
            ->addColumn('name', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('next_number', 'biginteger', ['signed' => false, 'null' => false, 'default' => 1])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
            ->create();
        $this->execute("INSERT INTO invoice_sequences (name, next_number, updated_at) VALUES ('fiscal_documents', 1, NOW())");

        $this->table('invoices')
            ->addColumn('order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('parent_invoice_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('type', 'string', ['limit' => 24, 'null' => false, 'default' => 'invoice'])
            ->addColumn('number', 'string', ['limit' => 32, 'null' => true, 'default' => null])
            ->addColumn('status', 'string', ['limit' => 24, 'null' => false, 'default' => 'draft'])
            ->addColumn('issue_date', 'date', ['null' => true, 'default' => null])
            ->addColumn('tax_event_date', 'date', ['null' => true, 'default' => null])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'default' => 'EUR'])
            ->addColumn('seller_snapshot', 'json', ['null' => false])
            ->addColumn('buyer_snapshot', 'json', ['null' => false])
            ->addColumn('items_snapshot', 'json', ['null' => false])
            ->addColumn('subtotal_net', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('discount_net', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('shipping_net', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('tax_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('total_gross', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('pdf_path', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('issued_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('cancelled_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['number'], ['unique' => true, 'name' => 'invoices_number_unique'])
            ->addIndex(['order_id', 'type'], ['name' => 'invoices_order_type_index'])
            ->addIndex(['status', 'issue_date'], ['name' => 'invoices_status_date_index'])
            ->addIndex(['parent_invoice_id'], ['name' => 'invoices_parent_index'])
            ->addForeignKey('order_id', 'orders', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('parent_invoice_id', 'invoices', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('invoices')->drop()->save();
        $this->table('invoice_sequences')->drop()->save();
        $this->table('orders')
            ->removeColumn('invoice_requested')->removeColumn('invoice_company')->removeColumn('invoice_eik')
            ->removeColumn('invoice_vat_number')->removeColumn('invoice_address')->removeColumn('invoice_mol')->update();
    }
}
