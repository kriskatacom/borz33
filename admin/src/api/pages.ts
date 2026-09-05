import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type PageFieldType = 'text' | 'textarea' | 'file';

export type AdminPageField = {
  id: number;
  name: string;
  slug: string;
  field_type: PageFieldType | string;
  value: string | null;
  media_file_id: number | null;
  media: MediaFile | null;
  is_required: boolean;
  sort_order: number;
};

export type PageListItem = {
  id: number;
  title: string;
  slug: string;
  parent_id: number | null;
  parent: { id: number; title: string; slug: string } | null;
  page_template_id?: number;
  page_template: { id: number; name: string; slug: string } | null;
  is_active: boolean;
  sort_order: number;
  fields_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type AdminPage = PageListItem & {
  content: string | null;
  meta_title: string | null;
  meta_description: string | null;
  fields: AdminPageField[];
};

export type PageTemplate = {
  id: number;
  name: string;
  slug: string;
  is_default: boolean;
};

export type PageListFilters = {
  q?: string;
  status?: string;
  parent?: string;
  page?: number;
  per_page?: number;
};

export type PageListData = {
  pages: PageListItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export type PageFieldPayload = {
  id?: number;
  name: string;
  slug?: string | null;
  field_type: PageFieldType;
  value?: string | null;
  media_file_id?: number | null;
  is_required: boolean;
  sort_order: number;
};

export type PagePayload = {
  title: string;
  slug?: string | null;
  parent_id?: number | null;
  page_template_id?: number;
  is_active: boolean;
  sort_order?: number;
  content?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
  fields?: PageFieldPayload[];
};

export function listPages(token: string, filters: PageListFilters) {
  return apiRequest<PageListData>('/admin/pages', { token, query: filters });
}

export function listPageTree(token: string) {
  return apiRequest<{ pages: Array<{ id: number; title: string; slug: string; parent_id: number | null; sort_order: number }> }>(
    '/admin/pages/tree',
    { token }
  );
}

export function listPageTemplates(token: string) {
  return apiRequest<{ templates: PageTemplate[] }>('/admin/pages/templates', { token });
}

export function getPage(token: string, id: number) {
  return apiRequest<{ page: AdminPage }>(`/admin/pages/${id}`, { token });
}

export function createPage(token: string, body: PagePayload) {
  return apiRequest<{ page: AdminPage }>('/admin/pages', { method: 'POST', token, body });
}

export function updatePage(token: string, id: number, body: PagePayload) {
  return apiRequest<{ page: AdminPage }>(`/admin/pages/${id}`, { method: 'PATCH', token, body });
}

export function deletePage(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/pages/${id}`, { method: 'DELETE', token });
}

export function restorePage(token: string, id: number) {
  return apiRequest<{ page: AdminPage }>(`/admin/pages/${id}/restore`, { method: 'POST', token });
}
