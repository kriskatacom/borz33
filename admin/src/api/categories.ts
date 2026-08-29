import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type CategoryListItem = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  parent: { id: number; name: string; slug: string } | null;
  media_file_id: number | null;
  media: MediaFile | null;
  is_active: boolean;
  sort_order: number;
  products_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type AdminCategory = CategoryListItem;

export type CategoryTreeNode = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  sort_order: number;
};

export type CategoryListFilters = {
  q?: string;
  status?: string;
  parent?: string;
  page?: number;
  per_page?: number;
};

export type CategoryListData = {
  categories: CategoryListItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export type CategoryPayload = {
  name: string;
  slug?: string | null;
  parent_id?: number | null;
  media_file_id?: number | null;
  is_active: boolean;
  sort_order?: number;
};

export function listCategories(token: string, filters: CategoryListFilters) {
  return apiRequest<CategoryListData>('/admin/categories', { token, query: filters });
}

export function listCategoryTree(token: string) {
  return apiRequest<{ categories: CategoryTreeNode[] }>('/admin/categories/tree', { token });
}

export function getCategory(token: string, id: number) {
  return apiRequest<{ category: AdminCategory }>(`/admin/categories/${id}`, { token });
}

export function createCategory(token: string, body: CategoryPayload) {
  return apiRequest<{ category: AdminCategory }>('/admin/categories', { method: 'POST', token, body });
}

export function updateCategory(token: string, id: number, body: CategoryPayload) {
  return apiRequest<{ category: AdminCategory }>(`/admin/categories/${id}`, { method: 'PATCH', token, body });
}

export function deleteCategory(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/categories/${id}`, { method: 'DELETE', token });
}

export function restoreCategory(token: string, id: number) {
  return apiRequest<{ category: AdminCategory }>(`/admin/categories/${id}/restore`, { method: 'POST', token });
}
