import { useEffect, useId, useState, type ReactNode } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { LogOut, Menu, X } from 'lucide-react';
import { navItems } from '@/app/nav';
import { useAppDispatch, useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { logout } from '@/features/auth/authThunks';

type AdminLayoutProps = {
  children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
  const dispatch = useAppDispatch();
  const user = useAppSelector((state) => state.auth.user);
  const [menuOpen, setMenuOpen] = useState(false);
  const menuId = useId();
  const location = useLocation();
  const displayName = user ? `${user.first_name} ${user.last_name}`.trim() : 'Екип';

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!menuOpen) {
      return;
    }

    function onKey(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setMenuOpen(false);
      }
    }

    document.body.classList.add('menu-open');
    window.addEventListener('keydown', onKey);

    return () => {
      document.body.classList.remove('menu-open');
      window.removeEventListener('keydown', onKey);
    };
  }, [menuOpen]);

  return (
    <div className="admin-shell">
      <header className="admin-topbar">
        <button
          type="button"
          className="icon-btn"
          aria-expanded={menuOpen}
          aria-controls={menuId}
          onClick={() => setMenuOpen((open) => !open)}
        >
          <span className="sr-only">{menuOpen ? 'Затвори менюто' : 'Отвори менюто'}</span>
          {menuOpen ? <X className="size-5" aria-hidden /> : <Menu className="size-5" aria-hidden />}
        </button>
        <p className="brand">Borz33</p>
        <Button type="button" variant="ghost" size="sm" onClick={() => void dispatch(logout())}>
          <LogOut />
          Изход
        </Button>
      </header>

      {menuOpen ? (
        <button type="button" className="nav-backdrop" aria-label="Затвори менюто" onClick={() => setMenuOpen(false)} />
      ) : null}

      <aside id={menuId} className={`admin-sidebar ${menuOpen ? 'is-open' : ''}`}>
        <div className="sidebar-brand">
          <p className="eyebrow">Админ панел</p>
          <p className="brand">Borz33</p>
        </div>
        <nav className="side-nav" aria-label="Основна навигация">
          {navItems.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.to === '/'} className="nav-link">
              <span>{item.label}</span>
              <small>{item.hint}</small>
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-user">
          <p>{displayName}</p>
          <p className="muted-line">{user?.email}</p>
          <Button type="button" variant="outline" onClick={() => void dispatch(logout())}>
            <LogOut />
            Изход
          </Button>
        </div>
      </aside>

      <div className="admin-main">{children}</div>

      <nav className="bottom-nav" aria-label="Бърза навигация">
        {navItems
          .filter((item) => item.mobile)
          .map((item) => (
            <NavLink key={item.to} to={item.to} end={item.to === '/'} className="bottom-link">
              {item.label}
            </NavLink>
          ))}
        <button type="button" className="bottom-link" onClick={() => setMenuOpen(true)}>
          <Menu className="size-4" aria-hidden />
          Още
        </button>
      </nav>
    </div>
  );
}
