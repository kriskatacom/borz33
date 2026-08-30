import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ApiError } from '@/api/client';
import { listMessages, type ContactMessage } from '@/api/messages';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { DataTable, DATA_TABLE_PAGE_SIZES, DEFAULT_PAGE_SIZE } from '@/components/data-table/DataTable';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Field } from '@/components/ui/Field';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { getMessagesColumns } from '@/features/messages/messagesColumns';
import { toast } from '@/lib/toast';

function pageSize(raw: string | null) { const value = Number(raw); return (DATA_TABLE_PAGE_SIZES as readonly number[]).includes(value) ? value : DEFAULT_PAGE_SIZE; }

export function MessagesPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [params, setParams] = useSearchParams();
  const [search, setSearch] = useState(params.get('q') ?? '');
  const [messages, setMessages] = useState<ContactMessage[]>([]);
  const [unread, setUnread] = useState(0);
  const [pagination, setPagination] = useState({ total: 0, lastPage: 1 });
  const [busy, setBusy] = useState(true);
  const columns = useMemo(() => getMessagesColumns(), []);
  const filters = useMemo(() => ({ q: params.get('q') ?? '', status: params.get('status') ?? 'all', page: Number(params.get('page') ?? '1') || 1, per_page: pageSize(params.get('per_page')) }), [params]);
  useGlobalLoading(busy);

  function update(next: Record<string, string>, reset = true) { const merged = new URLSearchParams(params); Object.entries(next).forEach(([key, value]) => value && value !== 'all' ? merged.set(key, value) : merged.delete(key)); if (reset) merged.delete('page'); setParams(merged); }
  useEffect(() => { const handle = window.setTimeout(() => { if (search !== (params.get('q') ?? '')) update({ q: search }); }, 300); return () => window.clearTimeout(handle); }, [search, params]);
  useEffect(() => { let cancelled = false; setBusy(true); void listMessages(token, filters).then((response) => { if (cancelled) return; setMessages(response.data.messages); setUnread(response.data.unread_count); setPagination({ total: response.data.pagination.total, lastPage: response.data.pagination.last_page }); }).catch((error) => { if (!cancelled) toast.error(error instanceof ApiError ? error.message : 'Съобщенията не можаха да се заредят.'); }).finally(() => { if (!cancelled) setBusy(false); }); return () => { cancelled = true; }; }, [filters, token]);

  return <div className="page"><PageHeader title={`Съобщения${unread > 0 ? ` (${unread})` : ''}`} help="Запитванията, изпратени от контактната форма на магазина." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Съобщения' }]} /><form className="filters" onSubmit={(event) => event.preventDefault()}><Field id="messages-q" label="Търсене" help="Тема, съдържание, име или имейл." value={search} placeholder="Търсене в съобщенията" onChange={(event) => setSearch(event.target.value)} /><div className="field"><LabelWithHelp htmlFor="messages-status" label="Статус" help="Филтрирайте непрочетените запитвания." /><Select value={filters.status} onValueChange={(value) => update({ status: value })}><SelectTrigger id="messages-status" className="min-h-12 w-full"><SelectValue /></SelectTrigger><SelectContent><SelectItem value="all">Всички</SelectItem><SelectItem value="unread">Непрочетени</SelectItem><SelectItem value="read">Прочетени</SelectItem></SelectContent></Select></div></form><DataTable columns={columns} data={messages} loading={busy} emptyMessage="Няма съобщения за избраните филтри." caption="Контактни съобщения" pagination={{ page: filters.page, lastPage: pagination.lastPage, total: pagination.total, pageSize: filters.per_page, onPageChange: (page) => update({ page: String(page) }, false), onPageSizeChange: (size) => update({ per_page: size === DEFAULT_PAGE_SIZE ? '' : String(size) }) }} /></div>;
}
