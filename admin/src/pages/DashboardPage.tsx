import { Link } from 'react-router-dom';
import { navItems } from '@/app/nav';
import { useAppSelector } from '@/app/hooks';

export function DashboardPage() {
  const user = useAppSelector((state) => state.auth.user);
  const firstName = user?.first_name ?? 'екип';
  const links = navItems.filter((item) => item.to !== '/');

  return (
    <div className="page">
      <header className="page-head">
        <p className="eyebrow">Табло</p>
        <h1>Добре дошли, {firstName}.</h1>
        <p className="muted">Най-важното за деня е събрано тук. Модулите ще се пълнят с реални данни, когато ги изградим.</p>
      </header>

      <section className="stat-grid" aria-label="Обобщение">
        <article className="stat-card">
          <p>Нови поръчки</p>
          <strong>—</strong>
        </article>
        <article className="stat-card accent">
          <p>За обработка</p>
          <strong>—</strong>
        </article>
        <article className="stat-card">
          <p>Ниски наличности</p>
          <strong>—</strong>
        </article>
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
