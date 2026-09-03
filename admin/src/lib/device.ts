import { STORAGE_KEYS } from '@/app/constants';

export function getOrCreateDeviceUuid(): string {
  const existing = window.localStorage.getItem(STORAGE_KEYS.deviceUuid);

  if (existing && isUuid(existing)) {
    return existing;
  }

  const uuid = crypto.randomUUID();
  window.localStorage.setItem(STORAGE_KEYS.deviceUuid, uuid);

  return uuid;
}

function isUuid(value: string): boolean {
  return /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
}

export function deviceName(): string {
  const ua = window.navigator.userAgent;
  const compact = ua.replace(/\s+/g, ' ').slice(0, 120);

  return compact || 'Админ панел';
}
