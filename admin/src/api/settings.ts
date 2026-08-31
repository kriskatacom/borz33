import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type SiteSettings = { logo_media_file_id: number | null; logo: MediaFile | null; vat_enabled: boolean };

export function getSiteSettings(token: string) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { token });
}

export function updateSiteSettings(token: string, body: Partial<Pick<SiteSettings, 'logo_media_file_id' | 'vat_enabled'>>) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { method: 'PATCH', token, body });
}
