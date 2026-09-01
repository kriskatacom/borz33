import { apiRequest } from '@/api/client';

export type DashboardSummary = {
  products_active: number;
  low_stock: number;
  banners_active: number;
  customers: number;
  categories_active: number;
  pages_active: number;
  media: number;
  orders_today: number;
  orders_month: number;
  revenue_month: number;
  pending_orders: number;
  invoices_month: number;
  recent_orders: Array<{ id: number; number: string; customer: string; status: string; total: number; currency: string; created_at: string | null }>;
};

export function getDashboard(token: string) {
  return apiRequest<DashboardSummary>('/admin/dashboard', { token });
}
