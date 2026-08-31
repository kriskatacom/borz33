import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type EcontSettings = {
  environment: 'demo' | 'production';
  production_username: string;
  production_password_configured: boolean;
  production_password_masked: string;
  production_verified_at: string | null;
};

export type SiteSettings = { logo_media_file_id: number | null; logo: MediaFile | null; vat_enabled: boolean; free_shipping_threshold: number; econt: EcontSettings };

export function getSiteSettings(token: string) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { token });
}

export function updateSiteSettings(token: string, body: { logo_media_file_id?: number | null; vat_enabled?: boolean; free_shipping_threshold?: number; econt_environment?: 'demo' | 'production'; econt_production_username?: string; econt_production_password?: string }) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { method: 'PATCH', token, body });
}

export function testEcontConnection(token: string, body: { environment: 'demo' | 'production'; username?: string; password?: string }) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings/econt/test', { method: 'POST', token, body });
}
