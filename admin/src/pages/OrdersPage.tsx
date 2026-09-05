import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { listOrders, type OrderListItem } from '@/api/orders';
import { getUser, listUsers, type ManagedUser } from '@/api/users';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable, DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ORDER_STATUSES } from '@/features/orders/orderFormat';
import { getOrdersColumns } from '@/features/orders/ordersColumns';
import { toast } from '@/lib/toast';

function pageSize(raw: string | null): number {
  const value = Number(raw);
  return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE;
}

function dateValue(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

function currentMonthDates(): { from: string; to: string } {
  const now = new Date();
  return {
    from: dateValue(new Date(now.getFullYear(), now.getMonth(), 1)),
    to: dateValue(new Date(now.getFullYear(), now.getMonth() + 1, 0)),
  };
}

function UserFilter({ token, value, onChange }: { token: string; value: string; onChange: (value: string) => void }) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [users, setUsers] = useState<ManagedUser[]>([]);
  const [selected, setSelected] = useState<ManagedUser | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!value) {
      setSelected(null);
      return;
    }

    let cancelled = false;
    void getUser(token, Number(value)).then((response) => {
      if (!cancelled) setSelected(response.data.user);
    }).catch(() => { if (!cancelled) setSelected(null); });
    return () => { cancelled = true; };
  }, [token, value]);

  useEffect(() => {
    if (!open) return;
    const handle = window.setTimeout(() => {
      setLoading(true);
      void listUsers(token, { q: search.trim(), role: 'customer', status: 'active', per_page: 20 }).then((response) => {
        setUsers(response.data.users);
      }).catch(() => setUsers([])).finally(() => setLoading(false));
    }, 250);
    return () => window.clearTimeout(handle);
  }, [open, search, token]);

  return <div className="field"><LabelWithHelp htmlFor="orders-user" label="Потребител" help="Филтрира поръчките по конкретен клиент. Въведете име или имейл, за да намерите потребителя."/><Popover open={open} onOpenChange={(next) => { setOpen(next); if (!next) setSearch(''); }}><PopoverTrigger asChild><button id="orders-user" type="button" role="combobox" aria-expanded={open} className="flex min-h-12 w-full items-center justify-between gap-2 rounded-[6px] border border-input bg-field px-3 py-2 text-left text-base text-foreground outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"><span className={selected ? 'truncate' : 'truncate text-muted-foreground'}>{selected ? `${selected.first_name} ${selected.last_name} · ${selected.email}` : 'Всички потребители'}</span><span aria-hidden="true">⌄</span></button></PopoverTrigger><PopoverContent align="start" className="w-[min(32rem,calc(100vw-2rem))] p-2"><input autoFocus className="mb-2 h-10 w-full rounded-[6px] border border-input bg-field px-3 text-base text-foreground outline-none focus:border-ring" placeholder="Търси име или имейл" value={search} onChange={(event) => setSearch(event.target.value)} aria-label="Търсене на потребител"/><div className="grid max-h-64 gap-1 overflow-y-auto">{value ? <button type="button" className="w-full rounded-[6px] px-3 py-2 text-left text-sm text-muted-foreground hover:bg-accent" onClick={() => { onChange(''); setSelected(null); setOpen(false); setSearch(''); }}>Покажи всички потребители</button> : null}{loading ? <p className="m-0 p-2 text-sm text-muted-foreground">Търсене…</p> : users.length === 0 ? <p className="m-0 p-2 text-sm text-muted-foreground">Няма намерени потребители.</p> : users.map((user) => <button type="button" key={user.id} className={`w-full rounded-[6px] px-3 py-2 text-left text-sm hover:bg-accent ${String(user.id) === value ? 'bg-accent font-semibold' : ''}`} onClick={() => { onChange(String(user.id)); setSelected(user); setOpen(false); setSearch(''); }}><span className="block">{user.first_name} {user.last_name}</span><small className="text-muted-foreground">{user.email}</small></button>)}</div></PopoverContent></Popover></div>;
}

export function OrdersPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [orders, setOrders] = useState<OrderListItem[]>([]);
  const [pagination, setPagination] = useState({ total: 0, lastPage: 1 });
  const [busy, setBusy] = useState(true);
  const [filterDialogOpen, setFilterDialogOpen] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  useGlobalLoading(busy);
  const columns = useMemo(() => getOrdersColumns(), []);
  const defaultDates = useMemo(currentMonthDates, []);
  const filters = useMemo(() => ({
    q: params.get('q') ?? '',
    user_id: Number(params.get('user_id') ?? '0') || undefined,
    status: params.get('status') ?? 'all',
    delivery_method: params.get('delivery_method') ?? 'all',
    payment_method: params.get('payment_method') ?? 'all',
    date_from: params.get('date_from') ?? defaultDates.from,
    date_to: params.get('date_to') ?? defaultDates.to,
    page: Number(params.get('page') ?? '1') || 1,
    per_page: pageSize(params.get('per_page')),
  }), [defaultDates, params]);

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
      <PageHeader title="Поръчки" help="Преглеждайте новите поръчки, данните за доставка и плащане и управлявайте изпълнението им." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Поръчки' }]} actions={<Button type="button" variant="outline" onClick={() => setFilterDialogOpen(true)}>Филтри (7)</Button>} />
      {filterDialogOpen ? <div className="dialog-root"><button type="button" className="dialog-backdrop" aria-label="Затвори филтрите" onClick={() => setFilterDialogOpen(false)} /><div className="dialog dialog-wide orders-filters-dialog" role="dialog" aria-modal="true" aria-labelledby="orders-filters-title"><header className="orders-filters-dialog-header"><h2 id="orders-filters-title">Филтри на поръчките</h2><Button type="button" variant="ghost" size="icon" aria-label="Затвори филтрите" onClick={() => setFilterDialogOpen(false)}>×</Button></header><form className="filters" onSubmit={(event) => event.preventDefault()}>
        <Field id="orders-q" label="Търсене" help="Номер на поръчка, име или имейл на клиента." value={search} placeholder="Номер, име или имейл" onChange={(event) => setSearch(event.target.value)} />
        <UserFilter token={token} value={params.get('user_id') ?? ''} onChange={(value) => updateParams({ user_id: value })} />
        <div className="field"><LabelWithHelp htmlFor="orders-status" label="Статус" help="Етап на обработка." /><Select value={filters.status} onValueChange={(value) => updateParams({ status: value })}><SelectTrigger id="orders-status" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички статуси</SelectItem>{ORDER_STATUSES.map((item) => <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>)}</SelectContent></Select></div>
        <div className="field"><LabelWithHelp htmlFor="orders-delivery" label="Доставка" help="Начин на получаване." /><Select value={filters.delivery_method} onValueChange={(value) => updateParams({ delivery_method: value })}><SelectTrigger id="orders-delivery" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="office">Офис на Еконт</SelectItem><SelectItem value="address">До адрес</SelectItem></SelectContent></Select></div>
        <div className="field"><LabelWithHelp htmlFor="orders-payment" label="Плащане" help="Избран метод на плащане." /><Select value={filters.payment_method} onValueChange={(value) => updateParams({ payment_method: value })}><SelectTrigger id="orders-payment" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="cash_on_delivery">Наложен платеж</SelectItem></SelectContent></Select></div>
        <Field id="orders-date-from" label="От дата" type="date" value={filters.date_from} onChange={(event) => updateParams({ date_from: event.target.value })} />
        <Field id="orders-date-to" label="До дата" type="date" value={filters.date_to} min={filters.date_from} onChange={(event) => updateParams({ date_to: event.target.value })} />
      </form><footer className="dialog-actions filter-dialog-actions"><Button type="button" variant="outline" onClick={() => setFilterDialogOpen(false)}>Затвори</Button></footer></div></div> : null}
      {message ? <p className="form-message is-error" role="alert">{message}</p> : null}
      <DataTable columns={columns} data={orders} loading={busy} emptyMessage="Няма поръчки за избраните филтри." caption="Поръчки" pagination={{ page: filters.page, lastPage: pagination.lastPage, total: pagination.total, pageSize: filters.per_page, onPageChange: (page) => updateParams({ page: String(page) }, false), onPageSizeChange: (size) => updateParams({ per_page: size === DEFAULT_PAGE_SIZE ? '' : String(size) }) }} />
    </div>
  );
}
