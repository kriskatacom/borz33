import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type SiteSettings = { logo_media_file_id: number | null; logo: MediaFile | null };

export function getSiteSettings(token: string) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { token });
}

export function updateSiteSettings(token: string, logo_media_file_id: number | null) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { method: 'PATCH', token, body: { logo_media_file_id } });
}
