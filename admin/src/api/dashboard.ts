import { apiRequest } from '@/api/client';

export type DashboardSummary = {
  products_active: number;
  low_stock: number;
  banners_active: number;
  customers: number;
  categories_active: number;
  pages_active: number;
  media: number;
};

export function getDashboard(token: string) {
  return apiRequest<DashboardSummary>('/admin/dashboard', { token });
}
