import { apiRequest } from '@/api/client';
import type { AdminUser } from '@/features/auth/authSlice';

export type AuthPayload = {
  requires_device_verification: boolean;
  user: AdminUser;
  token?: string;
  token_type?: string;
  expires_at?: string;
};

type DeviceFields = {
  email: string;
  device_uuid: string;
  device_name?: string;
};

export function loginAdmin(body: DeviceFields & { password: string }) {
  return apiRequest<AuthPayload>('/auth/admin/login', { method: 'POST', body });
}

export function verifyAdminDevice(body: DeviceFields & { code: string }) {
  return apiRequest<AuthPayload>('/auth/admin/login/device', { method: 'POST', body });
}

export function resendAdminDeviceCode(body: DeviceFields) {
  return apiRequest<Record<string, never>>('/auth/admin/login/device/resend', { method: 'POST', body });
}

export function forgotAdminPassword(email: string) {
  return apiRequest<Record<string, never>>('/auth/admin/password/forgot', {
    method: 'POST',
    body: { email },
  });
}

export function resetAdminPassword(body: {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}) {
  return apiRequest<Record<string, never>>('/auth/admin/password/reset', { method: 'POST', body });
}

export function fetchSession(token: string) {
  return apiRequest<{ user: AdminUser }>('/auth/me', { token });
}

export function logoutSession(token: string) {
  return apiRequest<Record<string, never>>('/auth/logout', { method: 'POST', token });
}
