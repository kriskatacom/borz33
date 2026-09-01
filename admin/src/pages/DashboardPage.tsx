import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getDashboard, type DashboardSummary } from '@/api/dashboard';
import { routes } from '@/app/constants';
import { navItems } from '@/app/nav';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { OrderStatusBadge } from '@/features/orders/orderFormat';
import { formatDateTime, formatMoney } from '@/lib/format';
import { toastError } from '@/lib/toast';

const emptySummary: DashboardSummary = {
  products_active: 0,
  low_stock: 0,
  banners_active: 0,
  customers: 0,
  categories_active: 0,
  pages_active: 0,
  media: 0,
  orders_today: 0,
  orders_month: 0,
  revenue_month: 0,
  pending_orders: 0,
  invoices_month: 0,
  recent_orders: [],
};

export function DashboardPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const user = useAppSelector((state) => state.auth.user);
  const firstName = user?.first_name ?? 'екип';
  const links = navItems.filter((item) => item.to !== '/');
  const [summary, setSummary] = useState<DashboardSummary>(emptySummary);
  const [busy, setBusy] = useState(true);
  useGlobalLoading(busy);

  useEffect(() => {
    let ignore = false;

    setBusy(true);
    void getDashboard(token)
      .then((response) => {
        if (!ignore) {
          setSummary(response.data);
        }
      })
      .catch((error) => {
        if (!ignore) {
          toastError(error, 'Таблото не можа да се зареди.');
        }
      })
      .finally(() => {
        if (!ignore) {
          setBusy(false);
        }
      });

    return () => {
      ignore = true;
    };
  }, [token]);

  return (
    <div className="page">
      <PageHeader
        title={`Добре дошли, ${firstName}.`}
        help="Актуално състояние на поръчките, приходите, каталога и документите."
        crumbs={[{ label: 'Табло' }]}
      />

      <section className="stat-grid" aria-label="Обобщение">
        <Link to={routes.orders} className="stat-card"><p>Поръчки днес</p><strong>{busy ? '—' : summary.orders_today}</strong></Link>
        <Link to={routes.orders} className="stat-card"><p>Поръчки този месец</p><strong>{busy ? '—' : summary.orders_month}</strong></Link>
        <Link to={routes.accounting} className="stat-card"><p>Оборот този месец</p><strong>{busy ? '—' : formatMoney(summary.revenue_month)}</strong></Link>
        <Link to={routes.orders} className={`stat-card${summary.pending_orders > 0 ? ' accent' : ''}`}><p>Чакащи поръчки</p><strong>{busy ? '—' : summary.pending_orders}</strong></Link>
        <Link to={routes.invoices} className="stat-card"><p>Фактури този месец</p><strong>{busy ? '—' : summary.invoices_month}</strong></Link>
        <Link to={routes.products} className="stat-card">
          <p>Активни продукти</p>
          <strong>{busy ? '—' : summary.products_active}</strong>
        </Link>
        <Link to={routes.products} className={`stat-card${summary.low_stock > 0 ? ' accent' : ''}`}>
          <p>Ниски наличности</p>
          <strong>{busy ? '—' : summary.low_stock}</strong>
        </Link>
        <Link to={routes.banners} className="stat-card">
          <p>Активни банери</p>
          <strong>{busy ? '—' : summary.banners_active}</strong>
        </Link>
      </section>

      <section className="dashboard-recent">
        <div className="flex items-center justify-between gap-3"><h2 className="section-label">Последни поръчки</h2><Link to={routes.orders}>Всички поръчки</Link></div>
        {busy ? <p className="dashboard-empty">Зареждане на последните поръчки…</p> : summary.recent_orders.length === 0 ? <p className="dashboard-empty">Все още няма поръчки.</p> : <div className="dashboard-order-list">{summary.recent_orders.map((order) => <Link key={order.id} to={`/orders/${order.id}`} className="dashboard-order-row"><span><strong>#{order.number}</strong><small>{order.customer} · {formatDateTime(order.created_at)}</small></span><span className="text-right"><strong>{formatMoney(order.total)}</strong><OrderStatusBadge status={order.status} /></span></Link>)}</div>}
      </section>

      <section>
        <h2 className="section-label">Бързи връзки</h2>
        <div className="link-grid">
          {links.map((item, index) => (
            <Link key={item.to} to={item.to} className="dash-card">
              <span className="dash-index">{String(index + 1).padStart(2, '0')}</span>
              <h3>{item.label}</h3>
              <p>{item.hint}</p>
            </Link>
          ))}
        </div>
      </section>
    </div>
  );
}
