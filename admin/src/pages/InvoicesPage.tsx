import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Download, Eye } from 'lucide-react';
import { exportInvoices, listInvoices, type Invoice } from '@/api/invoices';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { createDataTableHelper } from '@/components/data-table/columnHelper';
import { DataTable, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { DatePicker } from '@/components/ui/DatePicker';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatMoney } from '@/lib/format';
import { toastError } from '@/lib/toast';

const helper = createDataTableHelper<Invoice>();
const statusLabels = { draft: 'Чернова', issued: 'Издадена', cancelled: 'Анулирана', credited: 'Кредитирана' } as const;

export function InvoicesPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [data, setData] = useState<Invoice[]>([]);
  const [busy, setBusy] = useState(true);
  const [pagination, setPagination] = useState({ total: 0, lastPage: 1 });
  const filters = useMemo(() => ({ q: params.get('q') ?? '', status: params.get('status') ?? 'all', type: params.get('type') ?? 'all', date_from: params.get('date_from') ?? '', date_to: params.get('date_to') ?? '', page: Number(params.get('page') ?? 1), per_page: Number(params.get('per_page') ?? DEFAULT_PAGE_SIZE) }), [params]);
  function update(next: Record<string, string>, reset = true) { const updated = new URLSearchParams(params); Object.entries(next).forEach(([key, value]) => value && value !== 'all' ? updated.set(key, value) : updated.delete(key)); if (reset) updated.delete('page'); setParams(updated); }
  useEffect(() => { const timer = window.setTimeout(() => { if (search !== filters.q) update({ q: search }); }, 300); return () => window.clearTimeout(timer); }, [search, filters.q]);
  useEffect(() => { let cancelled = false; setBusy(true); void listInvoices(token, filters).then((response) => { if (cancelled) return; setData(response.data.invoices); setPagination({ total: response.data.pagination.total, lastPage: response.data.pagination.last_page }); }).catch((error) => toastError(error, 'Фактурите не можаха да се заредят.')).finally(() => { if (!cancelled) setBusy(false); }); return () => { cancelled = true; }; }, [token, filters]);
  const columns = useMemo(() => helper.columns([
    helper.accessor('number', { header: 'Номер', cell: ({ row, getValue }) => <Link className="font-bold" to={`/invoices/${row.original.id}`}>{getValue() ?? 'Чернова'}</Link> }),
    helper.accessor('type', { header: 'Документ', cell: ({ getValue }) => getValue() === 'credit_note' ? 'Кредитно известие' : 'Фактура' }),
    helper.accessor('buyer', { header: 'Клиент', cell: ({ getValue }) => <div><strong>{getValue().company}</strong><div className="text-sm text-muted-foreground">ЕИК {getValue().eik || '—'}</div></div> }),
    helper.accessor('order_number', { header: 'Поръчка', cell: ({ row, getValue }) => <Link to={`/orders/${row.original.order_id}`}>#{getValue()}</Link> }),
    helper.accessor('issue_date', { header: 'Дата', cell: ({ getValue }) => getValue() ?? '—' }),
    helper.accessor('total_gross', { header: 'Общо', cell: ({ getValue }) => <strong>{formatMoney(getValue())}</strong> }),
    helper.accessor('status', { header: 'Статус', cell: ({ getValue }) => statusLabels[getValue()] }),
    helper.display({ id: 'actions', header: '', cell: ({ row }) => <Button asChild size="icon" variant="ghost" aria-label="Преглед"><Link to={`/invoices/${row.original.id}`}><Eye /></Link></Button> }),
  ]), []);
  return <div className="page">
    <PageHeader title="Фактури" help="Неизменяем архив на фактури и кредитни известия." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Фактури' }]} actions={<Button variant="outline" onClick={() => void exportInvoices(token, filters).catch((error) => toastError(error, 'Експортът е неуспешен.'))}><Download />Експорт за период</Button>} />
    <form className="filters" onSubmit={(event) => event.preventDefault()}>
      <Field id="invoice-q" label="Търсене" help="Номер, клиент, ЕИК или поръчка." value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Фактура, клиент, ЕИК…" />
      <div className="field"><LabelWithHelp htmlFor="invoice-status" label="Статус" help="Статус на документа." /><Select value={filters.status} onValueChange={(value) => update({ status: value })}><SelectTrigger id="invoice-status"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem>{Object.entries(statusLabels).map(([value, label]) => <SelectItem key={value} value={value}>{label}</SelectItem>)}</SelectContent></Select></div>
      <div className="field"><LabelWithHelp htmlFor="invoice-type" label="Тип" help="Фактура или кредитно известие." /><Select value={filters.type} onValueChange={(value) => update({ type: value })}><SelectTrigger id="invoice-type"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="invoice">Фактури</SelectItem><SelectItem value="credit_note">Кредитни известия</SelectItem></SelectContent></Select></div>
      <div className="field"><label htmlFor="date-from">От дата</label><DatePicker id="date-from" label="От дата" value={filters.date_from} max={filters.date_to} onChange={(value) => update({ date_from: value })} /></div>
      <div className="field"><label htmlFor="date-to">До дата</label><DatePicker id="date-to" label="До дата" value={filters.date_to} min={filters.date_from} onChange={(value) => update({ date_to: value })} /></div>
    </form>
    <DataTable columns={columns} data={data} loading={busy} emptyMessage="Няма документи за избраните филтри." caption="Фактури" pagination={{ page: filters.page, lastPage: pagination.lastPage, total: pagination.total, pageSize: filters.per_page, onPageChange: (page) => update({ page: String(page) }, false), onPageSizeChange: (size) => update({ per_page: String(size) }) }} />
  </div>;
}
