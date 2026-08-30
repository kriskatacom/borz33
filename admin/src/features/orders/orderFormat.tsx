import type { OrderStatus } from '@/api/orders';
import { cn } from '@/lib/utils';

export const ORDER_STATUSES: Array<{ value: OrderStatus; label: string }> = [
  { value: 'pending', label: 'Нова' },
  { value: 'confirmed', label: 'Потвърдена' },
  { value: 'processing', label: 'Обработва се' },
  { value: 'shipped', label: 'Изпратена' },
  { value: 'delivered', label: 'Доставена' },
  { value: 'cancelled', label: 'Отказана' },
];

export function orderStatusLabel(status: string): string {
  return ORDER_STATUSES.find((item) => item.value === status)?.label ?? status;
}

export function OrderStatusBadge({ status }: { status: string }) {
  return <span className={cn('inline-flex rounded-full px-2.5 py-1 text-xs font-bold', status === 'cancelled' ? 'bg-destructive/12 text-destructive' : status === 'delivered' ? 'bg-primary/12 text-primary' : status === 'pending' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-muted text-foreground')}>{orderStatusLabel(status)}</span>;
}

export function deliveryLabel(value: string): string {
  if (value === 'office') return 'Офис на Еконт';
  if (value === 'address') return 'До адрес';
  return value || '—';
}

export function paymentLabel(value: string): string {
  if (value === 'cash_on_delivery') return 'Наложен платеж';
  if (value === 'bank_transfer') return 'Банков превод';
  if (value === 'card') return 'Карта';
  return value || '—';
}
