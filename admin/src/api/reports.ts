import { apiRequest } from '@/api/client';

export type ReportStatusMetric = { count: number; total: string };
export type ReportTopProduct = { name: string; sku: string | null; qty: number; revenue: string };
export type MonthlyRevenueReport = {
  id: number; year: number; month: number; currency: string; period_start: string; period_end: string;
  orders_count: number; delivered_orders_count: number; paid_orders_count: number; cancelled_orders_count: number; items_sold: number;
  gross_turnover: string; recognized_revenue: string; product_revenue: string; shipping_revenue: string; average_order_value: string; credit_notes_count: number; credit_notes_amount: string;
  status_breakdown: Record<string, ReportStatusMetric>; top_products: ReportTopProduct[]; generated_by: string; generated_at: string | null;
};

export function listReports(token: string) { return apiRequest<{ reports: MonthlyRevenueReport[] }>('/admin/reports', { token }); }
export function generateReport(token: string, period: string) { return apiRequest<{ report: MonthlyRevenueReport }>('/admin/reports', { method: 'POST', token, body: { period } }); }
