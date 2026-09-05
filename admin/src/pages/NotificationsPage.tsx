import { Link } from 'react-router-dom';
import { Archive, Bell, CheckCheck, ChevronLeft, ChevronRight, CircleAlert, ExternalLink, Info, Trash2, TriangleAlert, X } from 'lucide-react';
import { archiveAllNotifications, archiveNotification, deleteAllNotifications, deleteNotification, listNotifications, readAllNotifications, setNotificationRead, unarchiveNotification, type AdminNotification } from '@/api/notifications';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useEffect, useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { AdminPageSkeleton } from '@/components/admin-page-skeleton';
import { formatDateTime } from '@/lib/format';
import { toastError } from '@/lib/toast';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';

const icons = { info: Info, warning: TriangleAlert, critical: CircleAlert };
const NOTIFICATIONS_PER_PAGE = 20;

export function NotificationsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const [items, setItems] = useState<AdminNotification[]>([]);
  const [busy, setBusy] = useState(true);
  const [saving, setSaving] = useState(false);
  const [archivedView, setArchivedView] = useState(false);
  const [page, setPage] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const [mobileItem, setMobileItem] = useState<AdminNotification | null>(null);
  const [isMobile, setIsMobile] = useState(() => typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches);
  const [pagination, setPagination] = useState({ page: 1, per_page: NOTIFICATIONS_PER_PAGE, total: 0, last_page: 1 });
  const [confirmAction, setConfirmAction] = useState<any>(null);

  useEffect(() => {
    const media = window.matchMedia('(max-width: 767px)');
    const onChange = () => setIsMobile(media.matches);
    onChange();
    media.addEventListener('change', onChange);
    return () => media.removeEventListener('change', onChange);
  }, []);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    void listNotifications(token, { archived: archivedView, page, per_page: NOTIFICATIONS_PER_PAGE })
      .then((response) => {
        if (cancelled) return;
        setItems(response.data.notifications ?? []);
        setUnreadCount(response.data.unread_count ?? 0);
        setPagination(response.data.pagination ?? { page, per_page: NOTIFICATIONS_PER_PAGE, total: 0, last_page: 1 });
      })
      .catch((error) => { if (!cancelled) toastError(error, 'Известията не можаха да се заредят.'); })
      .finally(() => { if (!cancelled) setBusy(false); });
    return () => { cancelled = true; };
  }, [archivedView, page, token]);

  async function openMobileNotification(item: AdminNotification) {
    setMobileItem(item);
    if (item.read_at !== null) return;
    try {
      const response = await setNotificationRead(token, item.id, true);
      setMobileItem(response.data.notification);
      setItems((current) => current.map((candidate) => candidate.id === item.id ? response.data.notification : candidate));
      setUnreadCount((current) => Math.max(0, current - 1));
      window.dispatchEvent(new Event('admin:notifications-refresh'));
    } catch { /* Отварянето на drawer-а не трябва да блокира при проблем с маркирането. */ }
  }

  async function readAll() {
    setSaving(true);
    try {
      await readAllNotifications(token);
      setItems((current) => current.map((item) => ({ ...item, read_at: new Date().toISOString() })));
      setUnreadCount(0);
      window.dispatchEvent(new Event('admin:notifications-refresh'));
    } catch (error) { toastError(error, 'Известията не можаха да се обновят.'); }
    finally { setSaving(false); }
  }

  async function runConfirm() {
    if (!confirmAction) return;
    setSaving(true);
    try {
      if (confirmAction === 'archive-all') { await archiveAllNotifications(token); setItems([]); setPage(1); }
      else if (confirmAction === 'delete-all') { await deleteAllNotifications(token, archivedView); setItems([]); setPage(1); }
      else {
        if (confirmAction.type === 'archive') await archiveNotification(token, confirmAction.id);
        if (confirmAction.type === 'unarchive') await unarchiveNotification(token, confirmAction.id);
        if (confirmAction.type === 'delete') await deleteNotification(token, confirmAction.id);
        if (items.length === 1 && page > 1) setPage((current) => current - 1);
        else setItems((current) => current.filter((item) => item.id !== confirmAction.id));
      }
      setConfirmAction(null);
      setMobileItem(null);
      window.dispatchEvent(new Event('admin:notifications-refresh'));
    } catch (error) { toastError(error, 'Действието над известието не беше успешно.'); }
    finally { setSaving(false); }
  }

  const headerActions = <div className="flex flex-wrap gap-2"><Button variant="outline" disabled={saving} onClick={() => { setArchivedView((value) => !value); setPage(1); }}><Archive />{archivedView ? 'Активни известия' : 'Архивирани известия'}</Button>{!archivedView && unreadCount > 0 ? <Button variant="outline" disabled={saving} onClick={() => void readAll()}><CheckCheck />Прочети всички</Button> : null}{items.length > 0 ? <>{!archivedView ? <Button variant="outline" disabled={saving} onClick={() => setConfirmAction('archive-all')}><Archive />Архивирай всички</Button> : null}<Button variant="destructive" disabled={saving} onClick={() => setConfirmAction('delete-all')}><Trash2 />Изтрий всички</Button></> : null}</div>;
  const paginationControls = pagination.last_page > 1 ? <div className="mt-4 flex flex-wrap items-center justify-between gap-3"><p className="m-0 text-sm text-muted-foreground">Страница {pagination.page} от {pagination.last_page} · {pagination.total} известия</p><div className="flex gap-2"><Button variant="outline" disabled={busy || page <= 1} onClick={() => setPage((current) => current - 1)}><ChevronLeft />Предишна</Button><Button variant="outline" disabled={busy || page >= pagination.last_page} onClick={() => setPage((current) => current + 1)}>Следваща<ChevronRight /></Button></div></div> : null;

  return <div className="page"><PageHeader title="Известия" help="Важни събития от работата на магазина, които изискват внимание." crumbs={[{ label: 'Табло', to: routes.home }, { label: archivedView ? 'Архивирани известия' : 'Известия' }]} actions={headerActions} />{busy ? <AdminPageSkeleton sections={3} /> : items.length === 0 ? <div className="notification-empty"><Bell /><h2>Няма известия</h2><p>Тук ще се появяват важни събития, например ниска наличност на продукт.</p></div> : <><div className="notification-list">{items.map((item) => { const Icon = icons[item.level] ?? Info; const image = item.metadata?.image_url; const content = <><span className="notification-content"><strong>{item.title}</strong><p>{item.body}</p><small>{item.created_at ? formatDateTime(item.created_at) : ''}</small></span>{item.read_at === null ? <span className="notification-unread" aria-label="Непрочетено" /> : null}</>; return <article key={item.id} className={'notification-card' + (item.read_at === null ? ' is-unread' : '')}><div className="notification-row"><Link to={'/notifications/' + item.id} onClick={(event) => { if (isMobile) { event.preventDefault(); void openMobileNotification(item); } }} className="flex min-w-0 flex-1 items-start gap-3 text-inherit no-underline">{image ? <img className="notification-product-image" src={image} alt="" /> : <span className={'notification-icon is-' + item.level}><Icon /></span>}{content}</Link><div className="flex shrink-0 gap-1">{archivedView ? <Button type="button" variant="ghost" size="icon" aria-label="Разархивирай известието" disabled={saving} onClick={() => setConfirmAction({ type: 'unarchive', id: item.id })}><Archive /></Button> : <Button type="button" variant="ghost" size="icon" aria-label="Архивирай известието" disabled={saving} onClick={() => setConfirmAction({ type: 'archive', id: item.id })}><Archive /></Button>}<Button type="button" variant="ghost" size="icon" aria-label="Изтрий известието" disabled={saving} onClick={() => setConfirmAction({ type: 'delete', id: item.id })}><Trash2 /></Button></div></div></article>; })}</div>{paginationControls}</>}{mobileItem ? <div className="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label="Детайли за известие"><button type="button" className="absolute inset-0 bg-black/45" aria-label="Затвори" onClick={() => setMobileItem(null)} /><aside className="absolute inset-y-0 right-0 flex w-[min(92vw,26rem)] flex-col border-l border-border bg-card p-5 shadow-2xl"><header className="flex items-start justify-between gap-3 border-b border-border pb-4"><div className="flex items-center gap-3">{(() => { const Icon = icons[mobileItem.level] ?? Bell; return <span className={'notification-icon is-' + mobileItem.level}><Icon /></span>; })()}<div><p className="m-0 text-sm text-muted-foreground">Известие</p><h2 className="m-0 text-xl">{mobileItem.title}</h2></div></div><Button type="button" variant="ghost" size="icon" aria-label="Затвори" onClick={() => setMobileItem(null)}><X /></Button></header>{mobileItem.metadata?.image_url ? <img className="mt-5 max-h-56 w-full rounded-lg object-contain" src={mobileItem.metadata.image_url} alt="" /> : null}<div className="grid gap-4 overflow-y-auto py-5"><p className="m-0 text-base leading-7">{mobileItem.body}</p>{typeof mobileItem.metadata?.stock === 'number' ? <p className="m-0 text-base">Текуща наличност: <strong>{mobileItem.metadata.stock} бр.</strong></p> : null}<small className="text-muted-foreground">{mobileItem.created_at ? formatDateTime(mobileItem.created_at) : ''}</small>{mobileItem.link ? <Button asChild><Link to={mobileItem.link} onClick={() => setMobileItem(null)}><ExternalLink />Отвори свързания продукт</Link></Button> : null}</div></aside></div> : null}{confirmAction ? <ConfirmDialog title={confirmAction === 'delete-all' || (typeof confirmAction === 'object' && confirmAction.type === 'delete') ? 'Изтриване на известие' : confirmAction === 'archive-all' ? 'Архивиране на известие' : confirmAction.type === 'unarchive' ? 'Разархивиране на известие' : 'Архивиране на известие'} message={confirmAction === 'delete-all' ? 'Всички известия ще бъдат изтрити окончателно.' : confirmAction === 'archive-all' ? 'Всички активни известия ще бъдат преместени в архива.' : confirmAction.type === 'delete' ? 'Известието ще бъде изтрито окончателно.' : confirmAction.type === 'unarchive' ? 'Известието ще бъде върнато при активните известия.' : 'Известието ще бъде преместено в архива.'} confirmLabel={confirmAction === 'delete-all' || (typeof confirmAction === 'object' && confirmAction.type === 'delete') ? 'Изтрий' : confirmAction.type === 'unarchive' ? 'Разархивирай' : 'Архивирай'} busy={saving} onConfirm={() => void runConfirm()} onCancel={() => setConfirmAction(null)} /> : null}</div>;
}
