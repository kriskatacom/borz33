import { apiRequest } from '@/api/client';
import type { MediaFile } from '@/api/media';

export type EcontSettings = {
  environment: 'demo' | 'production';
  production_username: string;
  production_password_configured: boolean;
  production_password_masked: string;
  production_verified_at: string | null;
};

export type SiteSettings = { logo_media_file_id: number | null; logo: MediaFile | null; admin_background: string | null; admin_background_overlay: number; storefront_status: 'live' | 'development'; vat_enabled: boolean; free_shipping_threshold: number; low_stock_threshold: number; econt_operations_enabled: boolean; econt: EcontSettings };
export type AdminBackground = { value: string; label: string; help: string };
export type SitemapStatus = { url: string; generated: boolean; generated_at: string | null; checked_at?: string; counts: { pages: number; categories: number; products: number } };

export function getSiteSettings(token: string) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { token });
}

export function updateSiteSettings(token: string, body: { logo_media_file_id?: number | null; admin_background?: string | null; admin_background_overlay?: number; storefront_status?: 'live' | 'development'; vat_enabled?: boolean; free_shipping_threshold?: number; low_stock_threshold?: number; econt_operations_enabled?: boolean; econt_environment?: 'demo' | 'production'; econt_production_username?: string; econt_production_password?: string }) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings', { method: 'PATCH', token, body });
}

export function listAdminBackgrounds(token: string) {
  return apiRequest<{ backgrounds: AdminBackground[] }>('/admin/settings/admin-backgrounds', { token });
}

export function getSitemapStatus(token: string) {
  return apiRequest<{ sitemap: SitemapStatus }>('/admin/settings/sitemap', { token });
}

export function generateSitemap(token: string) {
  return apiRequest<{ sitemap: SitemapStatus }>('/admin/settings/sitemap/generate', { method: 'POST', token });
}

export function testEcontConnection(token: string, body: { environment: 'demo' | 'production'; username?: string; password?: string }) {
  return apiRequest<{ settings: SiteSettings }>('/admin/settings/econt/test', { method: 'POST', token, body });
}
