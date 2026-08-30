<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMonthlyRevenueReportsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('monthly_revenue_reports')
            ->addColumn('year', 'integer', ['signed' => false])
            ->addColumn('month', 'integer', ['signed' => false])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'EUR'])
            ->addColumn('period_start', 'date')
            ->addColumn('period_end', 'date')
            ->addColumn('orders_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('delivered_orders_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('cancelled_orders_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('items_sold', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('gross_turnover', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0])
            ->addColumn('recognized_revenue', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0])
            ->addColumn('product_revenue', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0])
            ->addColumn('shipping_revenue', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0])
            ->addColumn('average_order_value', 'decimal', ['precision' => 14, 'scale' => 2, 'default' => 0])
            ->addColumn('status_breakdown', 'json', ['null' => true, 'default' => null])
            ->addColumn('top_products', 'json', ['null' => true, 'default' => null])
            ->addColumn('generated_by', 'integer', ['signed' => false, 'null' => true, 'default' => null])
            ->addColumn('generated_at', 'datetime')
            ->addTimestamps()
            ->addIndex(['year', 'month', 'currency'], ['unique' => true, 'name' => 'monthly_reports_period_unique'])
            ->addForeignKey('generated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();
    }

    public function down(): void { $this->table('monthly_revenue_reports')->drop()->save(); }
}
