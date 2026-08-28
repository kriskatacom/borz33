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
