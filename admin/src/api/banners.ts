import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type BannerButton = {
  id: number;
  label: string;
  url: string;
  open_in_new_tab: boolean;
  sort_order: number;
};

export type BannerListItem = {
  id: number;
  title: string;
  slug: string;
  layout: string;
  media_file_id: number;
  media: MediaFile | null;
  is_active: boolean;
  sort_order: number;
  buttons_count: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type AdminBanner = BannerListItem & {
  text: string;
  buttons: BannerButton[];
};

export type BannerListFilters = {
  q?: string;
  status?: string;
  slug?: string;
  page?: number;
  per_page?: number;
};

export type BannerListData = {
  banners: BannerListItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export type BannerButtonPayload = {
  id?: number;
  label: string;
  url: string;
  open_in_new_tab: boolean;
  sort_order: number;
};

export type BannerPayload = {
  title: string;
  slug?: string | null;
  text: string;
  layout: string;
  media_file_id: number;
  is_active: boolean;
  sort_order?: number;
  buttons: BannerButtonPayload[];
};

export function listBanners(token: string, filters: BannerListFilters) {
  return apiRequest<BannerListData>('/admin/banners', { token, query: filters });
}

export function getBanner(token: string, id: number) {
  return apiRequest<{ banner: AdminBanner }>(`/admin/banners/${id}`, { token });
}

export function createBanner(token: string, body: BannerPayload) {
  return apiRequest<{ banner: AdminBanner }>('/admin/banners', { method: 'POST', token, body });
}

export function updateBanner(token: string, id: number, body: BannerPayload) {
  return apiRequest<{ banner: AdminBanner }>(`/admin/banners/${id}`, { method: 'PATCH', token, body });
}

export function deleteBanner(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/banners/${id}`, { method: 'DELETE', token });
}

export function restoreBanner(token: string, id: number) {
  return apiRequest<{ banner: AdminBanner }>(`/admin/banners/${id}/restore`, { method: 'POST', token });
}
