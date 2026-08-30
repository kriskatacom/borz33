import { apiRequest, apiUpload, type ApiEnvelope } from '@/api/client';

export type MediaKind = 'image' | 'video' | 'audio' | 'document' | 'other';

export type MediaFile = {
  id: number;
  url: string;
  original_name: string;
  mime: string;
  extension: string;
  kind: MediaKind | string;
  size: number;
  alt: string | null;
  uploaded_by: number | null;
  created_at: string | null;
  updated_at: string | null;
};

export type MediaListFilters = {
  q?: string;
  kind?: string;
  raster?: boolean;
  page?: number;
  per_page?: number;
};

export type MediaListData = {
  files: MediaFile[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

type MediaListCacheEntry = {
  expiresAt: number;
  request: Promise<ApiEnvelope<MediaListData>>;
};

const MEDIA_LIST_CACHE_TTL = 60_000;
const mediaListCache = new Map<string, MediaListCacheEntry>();

function mediaListCacheKey(token: string, filters: MediaListFilters): string {
  const values = Object.entries(filters)
    .filter(([, value]) => value !== undefined && value !== '')
    .sort(([left], [right]) => left.localeCompare(right));

  return `${token}\u0000${JSON.stringify(values)}`;
}

export function invalidateMediaListCache(): void {
  mediaListCache.clear();
}

export function listMedia(token: string, filters: MediaListFilters) {
  return apiRequest<MediaListData>('/admin/media', { token, query: filters });
}

export function listMediaCached(token: string, filters: MediaListFilters) {
  const key = mediaListCacheKey(token, filters);
  const cached = mediaListCache.get(key);

  if (cached && cached.expiresAt > Date.now()) return cached.request;
  if (cached) mediaListCache.delete(key);

  const request = listMedia(token, filters).catch((error) => {
    mediaListCache.delete(key);
    throw error;
  });
  mediaListCache.set(key, { expiresAt: Date.now() + MEDIA_LIST_CACHE_TTL, request });
  return request;
}

export function getMediaFile(token: string, id: number) {
  return apiRequest<{ file: MediaFile }>(`/admin/media/${id}`, { token });
}

export function uploadMediaFile(
  token: string,
  file: File,
  options: { signal?: AbortSignal; onProgress?: (percent: number) => void } = {}
) {
  const form = new FormData();
  form.append('file', file);

  return apiUpload<{ files: MediaFile[] }>('/admin/media', {
    token,
    form,
    signal: options.signal,
    onProgress: options.onProgress,
  }).then((response) => {
    invalidateMediaListCache();
    return response;
  });
}

export function updateMediaFile(token: string, id: number, body: { original_name?: string; alt?: string | null }) {
  return apiRequest<{ file: MediaFile }>(`/admin/media/${id}`, { method: 'PATCH', token, body }).then((response) => {
    invalidateMediaListCache();
    return response;
  });
}

export function deleteMediaFile(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/media/${id}`, { method: 'DELETE', token }).then((response) => {
    invalidateMediaListCache();
    return response;
  });
}
