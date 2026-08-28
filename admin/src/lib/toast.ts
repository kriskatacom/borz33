import { ApiError } from '@/api/client';

export type ToastKind = 'success' | 'error' | 'info';

export type ToastItem = {
  id: string;
  message: string;
  kind: ToastKind;
};

const MAX_TOASTS = 4;
const listeners = new Set<(items: ToastItem[]) => void>();
const timers = new Map<string, number>();
let items: ToastItem[] = [];

function emit() {
  for (const listener of listeners) {
    listener(items);
  }
}

export function dismissToast(id: string) {
  const timer = timers.get(id);

  if (timer !== undefined) {
    window.clearTimeout(timer);
    timers.delete(id);
  }

  items = items.filter((item) => item.id !== id);
  emit();
}

function show(message: string, kind: ToastKind) {
  const text = message.trim();

  if (text === '') {
    return;
  }

  const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const duration = kind === 'error' ? 6500 : 4200;

  items = [...items, { id, message: text, kind }].slice(-MAX_TOASTS);
  emit();
  timers.set(
    id,
    window.setTimeout(() => {
      dismissToast(id);
    }, duration)
  );
}

export const toast = {
  success(message: string) {
    show(message, 'success');
  },
  error(message: string) {
    show(message, 'error');
  },
  info(message: string) {
    show(message, 'info');
  },
};

export function toastError(error: unknown, fallback: string) {
  if (error instanceof ApiError) {
    const firstField = Object.values(error.fieldErrors())[0];
    toast.error(firstField || error.message || fallback);
    return;
  }

  toast.error(fallback);
}

export function subscribeToasts(listener: (next: ToastItem[]) => void) {
  listeners.add(listener);
  listener(items);

  return () => {
    listeners.delete(listener);
  };
}
