const formatter = new Intl.DateTimeFormat('bg-BG', {
  dateStyle: 'short',
  timeStyle: 'short',
});

export function formatDateTime(value: string | null): string {
  if (!value) {
    return '—';
  }

  const date = parseDate(value);

  if (!date) {
    return '—';
  }

  return formatter.format(date);
}

export function formatRelativeTime(value: string | null): string {
  if (!value) {
    return '—';
  }

  const date = parseDate(value);

  if (!date) {
    return '—';
  }

  const diffSec = Math.round((Date.now() - date.getTime()) / 1000);
  const abs = Math.abs(diffSec);
  const prefix = diffSec >= 0 ? 'преди' : 'след';

  if (abs < 45) {
    return diffSec >= 0 ? 'току-що' : 'след малко';
  }

  if (abs < 60 * 60) {
    return `${prefix} ${Math.max(1, Math.round(abs / 60))} мин`;
  }

  if (abs < 60 * 60 * 24) {
    return `${prefix} ${Math.max(1, Math.round(abs / 3600))} ч`;
  }

  if (abs < 60 * 60 * 24 * 7) {
    const n = Math.max(1, Math.round(abs / 86400));
    return `${prefix} ${n} ${n === 1 ? 'ден' : 'дни'}`;
  }

  if (abs < 60 * 60 * 24 * 30) {
    return `${prefix} ${Math.max(1, Math.round(abs / (86400 * 7)))} седм`;
  }

  if (abs < 60 * 60 * 24 * 365) {
    return `${prefix} ${Math.max(1, Math.round(abs / (86400 * 30)))} мес`;
  }

  return `${prefix} ${Math.max(1, Math.round(abs / (86400 * 365)))} г`;
}

function parseDate(value: string): Date | null {
  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? null : date;
}

const moneyFormatter = new Intl.NumberFormat('bg-BG', {
  style: 'currency',
  currency: 'BGN',
});

export function formatBytes(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes < 0) {
    return '—';
  }

  if (bytes < 1024) {
    return `${bytes} B`;
  }

  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
  }

  return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
}

export function formatMoney(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  const amount = typeof value === 'number' ? value : Number(value);

  if (!Number.isFinite(amount)) {
    return '—';
  }

  return moneyFormatter.format(amount);
}

export function roleLabel(role: string): string {
  if (role === 'admin') {
    return 'Администратор';
  }

  if (role === 'customer') {
    return 'Клиент';
  }

  return role;
}
