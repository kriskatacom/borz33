import { apiRequest, apiUpload } from '@/api/client';

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

export function listMedia(token: string, filters: MediaListFilters) {
  return apiRequest<MediaListData>('/admin/media', { token, query: filters });
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
  });
}

export function updateMediaFile(token: string, id: number, body: { original_name?: string; alt?: string | null }) {
  return apiRequest<{ file: MediaFile }>(`/admin/media/${id}`, { method: 'PATCH', token, body });
}

export function deleteMediaFile(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/media/${id}`, { method: 'DELETE', token });
}
