import { useLocation } from 'react-router-dom';
import { navItemByPath } from '@/app/nav';

export function ComingSoonPage() {
  const { pathname } = useLocation();
  const item = navItemByPath(pathname);

  return (
    <div className="page">
      <header className="page-head">
        <p className="eyebrow">{item?.label ?? 'Раздел'}</p>
        <h1>{item?.label ?? 'Предстои'}</h1>
        <p className="muted">{item?.hint ?? 'Този екран ще бъде добавен следващо.'} Засега разделът е подготвен в навигацията.</p>
      </header>
      <article className="placeholder-card">
        <p>Функционалността още не е свързана. Можете да се върнете към таблото и да отворите друг раздел.</p>
      </article>
    </div>
  );
}
