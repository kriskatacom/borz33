import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { getDashboard, type DashboardSummary } from '@/api/dashboard';
import { routes } from '@/app/constants';
import { navItems } from '@/app/nav';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { toastError } from '@/lib/toast';

const emptySummary: DashboardSummary = {
  products_active: 0,
  low_stock: 0,
  banners_active: 0,
  customers: 0,
  categories_active: 0,
  pages_active: 0,
  media: 0,
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
        help="Текущо състояние на каталога, банерите и наличностите."
        crumbs={[{ label: 'Табло' }]}
      />

      <section className="stat-grid" aria-label="Обобщение">
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
