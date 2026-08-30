import { apiRequest } from '@/api/client';

export type ContactMessage = {
  id: number;
  user_id: number | null;
  name: string;
  email: string;
  phone: string | null;
  subject: string;
  message: string;
  email_sent: boolean;
  read_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  replies?: ContactMessageReply[];
};

export type ContactMessageReply = { id: number; body: string; email_sent: boolean; sender_type: 'admin' | 'customer'; sender: string; created_at: string | null };

export function listMessages(token: string, filters: { q?: string; status?: string; page?: number; per_page?: number }) {
  return apiRequest<{ messages: ContactMessage[]; unread_count: number; pagination: { page: number; per_page: number; total: number; last_page: number } }>('/admin/messages', { token, query: filters });
}

export function getMessage(token: string, id: number) {
  return apiRequest<{ message: ContactMessage }>(`/admin/messages/${id}`, { token });
}

export function markMessage(token: string, id: number, read: boolean) {
  return apiRequest<{ message: ContactMessage }>(`/admin/messages/${id}`, { method: 'PATCH', token, body: { read } });
}

export function sendMessageReply(token: string, id: number, body: string) {
  return apiRequest<{ message: ContactMessage; email_sent: boolean }>(`/admin/messages/${id}/replies`, { method: 'POST', token, body: { body } });
}
