import { apiRequest } from '@/api/client';

export type AdminNotification = {
  id: number;
  type: string;
  level: 'info' | 'warning' | 'critical';
  title: string;
  body: string;
  link: string | null;
  metadata?: { image_url?: string | null; stock?: number; quantity?: number } | null;
  read_at: string | null;
  created_at: string | null;
};

export type NotificationsPagination = { page: number; per_page: number; total: number; last_page: number };

export function listNotifications(token: string, filters: { archived?: boolean; page?: number; per_page?: number } = {}) {
  return apiRequest<{ notifications: AdminNotification[]; unread_count: number; pagination: NotificationsPagination }>('/admin/notifications', { token, query: filters });
}
export function getNotification(token: string, id: number) { return apiRequest<{ notification: AdminNotification }>(`/admin/notifications/${id}`, { token }); }
export function setNotificationRead(token: string, id: number, read: boolean) {
  if (read) {
    return apiRequest<{ notification: AdminNotification }>(`/admin/notifications/${id}/read`, { method: 'POST', token });
  }

  return apiRequest<{ notification: AdminNotification }>(`/admin/notifications/${id}`, { method: 'PATCH', token, body: { read: false } });
}
export function readAllNotifications(token: string) { return apiRequest('/admin/notifications/read-all', { method: 'POST', token }); }
export function archiveNotification(token: string, id: number) { return apiRequest(`/admin/notifications/${id}/archive`, { method: 'POST', token }); }
export function unarchiveNotification(token: string, id: number) { return apiRequest(`/admin/notifications/${id}/unarchive`, { method: 'POST', token }); }
export function deleteNotification(token: string, id: number) { return apiRequest(`/admin/notifications/${id}`, { method: 'DELETE', token }); }
export function archiveAllNotifications(token: string) { return apiRequest('/admin/notifications/archive-all', { method: 'POST', token }); }
export function deleteAllNotifications(token: string, archived = false) { return apiRequest('/admin/notifications', { method: 'DELETE', token, query: { archived } }); }
