import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { listOrders, type OrderListItem } from '@/api/orders';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable, DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ORDER_STATUSES } from '@/features/orders/orderFormat';
import { getOrdersColumns } from '@/features/orders/ordersColumns';
import { toast } from '@/lib/toast';

function pageSize(raw: string | null): number {
  const value = Number(raw);
  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

export function OrdersPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [orders, setOrders] = useState<OrderListItem[]>([]);
  const [pagination, setPagination] = useState({ total: 0, lastPage: 1 });
  const [busy, setBusy] = useState(true);
  const [message, setMessage] = useState<string | null>(null);
  useGlobalLoading(busy);
  const columns = useMemo(() => getOrdersColumns(), []);
  const filters = useMemo(() => ({
    q: params.get('q') ?? '',
    status: params.get('status') ?? 'all',
    delivery_method: params.get('delivery_method') ?? 'all',
    payment_method: params.get('payment_method') ?? 'all',
    page: Number(params.get('page') ?? '1') || 1,
    per_page: pageSize(params.get('per_page')),
  }), [params]);

  function updateParams(next: Record<string, string>, resetPage = true) {
    const merged = new URLSearchParams(params);
    Object.entries(next).forEach(([key, value]) => value && value !== 'all' ? merged.set(key, value) : merged.delete(key));
    if (resetPage) merged.delete('page');
    setParams(merged);
  }

  useEffect(() => {
    const handle = window.setTimeout(() => {
      if (search !== (params.get('q') ?? '')) updateParams({ q: search });
    }, 300);
    return () => window.clearTimeout(handle);
  }, [search, params]);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    setMessage(null);
    void listOrders(token, filters).then((response) => {
      if (cancelled) return;
      setOrders(response.data.orders);
      setPagination({ total: response.data.pagination.total, lastPage: response.data.pagination.last_page });
    }).catch((error) => {
      if (cancelled) return;
      const text = error instanceof ApiError ? error.message : 'Поръчките не можаха да се заредят.';
      setMessage(text);
      setOrders([]);
      toast.error(text);
    }).finally(() => { if (!cancelled) setBusy(false); });
    return () => { cancelled = true; };
  }, [filters, token]);

  return (
    <div className="page">
      <PageHeader title="Поръчки" help="Преглеждайте новите поръчки, данните за доставка и плащане и управлявайте изпълнението им." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Поръчки' }]} />
      <form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field id="orders-q" label="Търсене" help="Номер, име, имейл или телефон." value={search} placeholder="Номер или клиент" onChange={(event) => setSearch(event.target.value)} />
        <div className="field"><LabelWithHelp htmlFor="orders-status" label="Статус" help="Етап на обработка." /><Select value={filters.status} onValueChange={(value) => updateParams({ status: value })}><SelectTrigger id="orders-status" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички статуси</SelectItem>{ORDER_STATUSES.map((item) => <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>)}</SelectContent></Select></div>
        <div className="field"><LabelWithHelp htmlFor="orders-delivery" label="Доставка" help="Начин на получаване." /><Select value={filters.delivery_method} onValueChange={(value) => updateParams({ delivery_method: value })}><SelectTrigger id="orders-delivery" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="office">Офис на Еконт</SelectItem><SelectItem value="address">До адрес</SelectItem></SelectContent></Select></div>
        <div className="field"><LabelWithHelp htmlFor="orders-payment" label="Плащане" help="Избран метод на плащане." /><Select value={filters.payment_method} onValueChange={(value) => updateParams({ payment_method: value })}><SelectTrigger id="orders-payment" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="cash_on_delivery">Наложен платеж</SelectItem><SelectItem value="bank_transfer">Банков превод</SelectItem><SelectItem value="card">Карта</SelectItem></SelectContent></Select></div>
      </form>
      {message ? <p className="form-message is-error" role="alert">{message}</p> : null}
      <DataTable columns={columns} data={orders} loading={busy} emptyMessage="Няма поръчки за избраните филтри." caption="Поръчки" pagination={{ page: filters.page, lastPage: pagination.lastPage, total: pagination.total, pageSize: filters.per_page, onPageChange: (page) => updateParams({ page: String(page) }, false), onPageSizeChange: (size) => updateParams({ per_page: size === DEFAULT_PAGE_SIZE ? '' : String(size) }) }} />
    </div>
  );
}
