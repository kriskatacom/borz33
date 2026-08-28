import { apiRequest, apiUpload } from '@/api/client';

export type ManagedUser = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  avatar_url?: string | null;
  role: 'admin' | 'customer' | string;
  is_active: boolean;
  email_verified_at: string | null;
  last_login_at: string | null;
  last_login_ip: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type UserListFilters = {
  q?: string;
  role?: string;
  status?: string;
  page?: number;
  per_page?: number;
};

export type UserListData = {
  users: ManagedUser[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export type UserPayload = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  role: string;
  is_active: boolean;
  password?: string;
  password_confirmation?: string;
};

export function listUsers(token: string, filters: UserListFilters) {
  return apiRequest<UserListData>('/admin/users', { token, query: filters });
}

export function getUser(token: string, id: number) {
  return apiRequest<{ user: ManagedUser }>(`/admin/users/${id}`, { token });
}

export function createUser(token: string, body: UserPayload) {
  return apiRequest<{ user: ManagedUser }>('/admin/users', { method: 'POST', token, body });
}

export function updateUser(token: string, id: number, body: UserPayload) {
  return apiRequest<{ user: ManagedUser }>(`/admin/users/${id}`, { method: 'PATCH', token, body });
}

export function deleteUser(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/users/${id}`, { method: 'DELETE', token });
}

export function restoreUser(token: string, id: number) {
  return apiRequest<{ user: ManagedUser }>(`/admin/users/${id}/restore`, { method: 'POST', token });
}

export function uploadUserAvatar(
  token: string,
  id: number,
  file: File,
  options: { signal?: AbortSignal; onProgress?: (percent: number) => void } = {}
) {
  const form = new FormData();
  form.append('image', file);

  return apiUpload<{ user: ManagedUser }>(`/admin/users/${id}/avatar`, {
    token,
    form,
    signal: options.signal,
    onProgress: options.onProgress,
  });
}

export function deleteUserAvatar(token: string, id: number) {
  return apiRequest<{ user: ManagedUser }>(`/admin/users/${id}/avatar`, { method: 'DELETE', token });
}
