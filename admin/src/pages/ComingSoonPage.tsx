import { useLocation } from 'react-router-dom';
import { routes } from '@/app/constants';
import { navItemByPath } from '@/app/nav';
import { PageHeader } from '@/components/page-header';

export function ComingSoonPage() {
  const { pathname } = useLocation();
  const item = navItemByPath(pathname);
  const title = item?.label ?? 'Предстои';

  return (
    <div className="page">
      <PageHeader
        title={title}
        help={item?.hint ?? 'Този екран ще бъде добавен следващо.'}
        crumbs={[
          { label: 'Табло', to: routes.home },
          { label: title },
        ]}
      />
      <article className="placeholder-card">
        <p>Функционалността още не е свързана. Можете да се върнете към таблото и да отворите друг раздел.</p>
      </article>
    </div>
  );
}
