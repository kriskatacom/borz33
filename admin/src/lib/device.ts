import { STORAGE_KEYS } from '@/app/constants';

export function getOrCreateDeviceUuid(): string {
  const existing = window.localStorage.getItem(STORAGE_KEYS.deviceUuid);

  if (existing) {
    return existing;
  }

  const uuid = crypto.randomUUID();
  window.localStorage.setItem(STORAGE_KEYS.deviceUuid, uuid);

  return uuid;
}

export function deviceName(): string {
  const ua = window.navigator.userAgent;
  const compact = ua.replace(/\s+/g, ' ').slice(0, 120);

  return compact || 'Админ панел';
}
