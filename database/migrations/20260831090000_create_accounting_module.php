<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAccountingModule extends AbstractMigration
{
    public function up(): void
    {
        $this->table('accounting_transactions')
            ->addColumn('order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('method', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false, 'default' => 'completed'])
            ->addColumn('amount', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false])
            ->addColumn('currency', 'string', ['limit' => 3, 'null' => false, 'default' => 'EUR'])
            ->addColumn('external_reference', 'string', ['limit' => 191, 'null' => true, 'default' => null])
            ->addColumn('notes', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('occurred_at', 'datetime', ['null' => false])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['order_id', 'type', 'status'], ['name' => 'accounting_tx_order_type_status'])
            ->addIndex(['occurred_at', 'method'], ['name' => 'accounting_tx_date_method'])
            ->addForeignKey('order_id', 'orders', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('created_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        $this->table('econt_reconciliations')
            ->addColumn('order_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('shipment_status', 'string', ['limit' => 16, 'null' => false, 'default' => 'sent'])
            ->addColumn('tracking_number_snapshot', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('cod_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('company_received_amount', 'decimal', ['precision' => 12, 'scale' => 2, 'null' => false, 'default' => 0])
            ->addColumn('received_at', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('notes', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('updated_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['order_id'], ['unique' => true, 'name' => 'econt_reconciliation_order_unique'])
            ->addIndex(['shipment_status', 'received_at'], ['name' => 'econt_reconciliation_status_date'])
            ->addForeignKey('order_id', 'orders', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        $this->table('accounting_period_closures')
            ->addColumn('period', 'string', ['limit' => 7, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false, 'default' => 'closed'])
            ->addColumn('summary_snapshot', 'json', ['null' => false])
            ->addColumn('package_path', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('closed_at', 'datetime', ['null' => false])
            ->addColumn('closed_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['period'], ['unique' => true, 'name' => 'accounting_closures_period_unique'])
            ->addForeignKey('closed_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        $this->table('accounting_audit_logs')
            ->addColumn('action', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 80, 'null' => false])
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('before_snapshot', 'json', ['null' => true, 'default' => null])
            ->addColumn('after_snapshot', 'json', ['null' => true, 'default' => null])
            ->addColumn('metadata', 'json', ['null' => true, 'default' => null])
            ->addColumn('actor_user_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addIndex(['entity_type', 'entity_id'], ['name' => 'accounting_audit_entity'])
            ->addIndex(['created_at', 'action'], ['name' => 'accounting_audit_date_action'])
            ->addForeignKey('actor_user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void
    {
        $this->table('accounting_audit_logs')->drop()->save();
        $this->table('accounting_period_closures')->drop()->save();
        $this->table('econt_reconciliations')->drop()->save();
        $this->table('accounting_transactions')->drop()->save();
    }
}
