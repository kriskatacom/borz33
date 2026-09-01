<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPaymentAndCreditTotalsToMonthlyReports extends AbstractMigration
{
    public function up(): void
    {
        $this->table('monthly_revenue_reports')
            ->addColumn('paid_orders_count', 'integer', ['signed' => false, 'null' => false, 'default' => 0, 'after' => 'delivered_orders_count'])
            ->addColumn('credit_notes_count', 'integer', ['signed' => false, 'null' => false, 'default' => 0, 'after' => 'average_order_value'])
            ->addColumn('credit_notes_amount', 'decimal', ['precision' => 14, 'scale' => 2, 'null' => false, 'default' => 0, 'after' => 'credit_notes_count'])
            ->update();
    }

    public function down(): void
    {
        $this->table('monthly_revenue_reports')
            ->removeColumn('credit_notes_amount')
            ->removeColumn('credit_notes_count')
            ->removeColumn('paid_orders_count')
            ->update();
    }
}
