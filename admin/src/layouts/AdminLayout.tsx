import { useEffect, useId, useState, type ReactNode } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { LogOut, Menu, PanelLeftClose, PanelLeftOpen, X } from 'lucide-react';
import { routes } from '@/app/constants';
import { navItems, navSections } from '@/app/nav';
import { useAppDispatch, useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { Tooltip } from '@/components/ui/Tooltip';
import { logout } from '@/features/auth/authThunks';
import { toast } from '@/lib/toast';
import { listMessages } from '@/api/messages';
import { listNotifications } from '@/api/notifications';
import { getSiteSettings } from '@/api/settings';
import { adminBackgroundCss } from '@/app/adminBackgrounds';
import { AdminAssistant } from '@/components/admin-assistant';

type AdminLayoutProps = {
  children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
  const dispatch = useAppDispatch();
  const user = useAppSelector((state) => state.auth.user);
  const [menuOpen, setMenuOpen] = useState(false);
  const [unreadMessages, setUnreadMessages] = useState(0);
  const [unreadNotifications, setUnreadNotifications] = useState(0);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(() => window.localStorage.getItem('admin-sidebar-collapsed') === '1');
  const menuId = useId();
  const location = useLocation();
  const displayName = user ? `${user.first_name} ${user.last_name}`.trim() : 'Екип';
  const token = useAppSelector((state) => state.auth.token) ?? '';

  useEffect(() => {
    if (!token) return;
    const applyBackground = (background: string | null, overlay = 48) => { const css = adminBackgroundCss(background); const opacity = Math.max(0, Math.min(80, Number(overlay) || 0)); document.documentElement.style.setProperty('--admin-background-image', css === 'none' ? 'none' : opacity === 0 ? css : `linear-gradient(rgb(0 0 0 / ${opacity}%), rgb(0 0 0 / ${opacity}%)), ${css}`); };
    void getSiteSettings(token).then((response) => applyBackground(response.data.settings.admin_background, response.data.settings.admin_background_overlay)).catch(() => applyBackground(null));
    const onBackgroundChanged = (event: Event) => { const detail = (event as CustomEvent<{ background?: string | null; overlay?: number }>).detail; applyBackground(detail?.background ?? null, detail?.overlay ?? 48); };
    window.addEventListener('admin:background-changed', onBackgroundChanged);
    return () => window.removeEventListener('admin:background-changed', onBackgroundChanged);
  }, [token]);

  useEffect(() => {
    if (!token) { setUnreadNotifications(0); return; }
    let cancelled = false;
    const refresh = () => { void listNotifications(token).then((response) => { if (!cancelled) setUnreadNotifications(response.data.unread_count); }).catch(() => undefined); };
    refresh();
    const interval = window.setInterval(refresh, 60_000);
    window.addEventListener('admin:notifications-refresh', refresh);
    return () => { cancelled = true; window.clearInterval(interval); window.removeEventListener('admin:notifications-refresh', refresh); };
  }, [token]);

  function toggleSidebar() {
    setSidebarCollapsed((collapsed) => {
      const next = !collapsed;
      window.localStorage.setItem('admin-sidebar-collapsed', next ? '1' : '0');
      return next;
    });
  }

  useEffect(() => {
    if (!token) { setUnreadMessages(0); return; }
    let cancelled = false;
    const refresh = () => { void listMessages(token, { page: 1, per_page: 1 }).then((response) => { if (!cancelled) setUnreadMessages(response.data.unread_count); }).catch(() => undefined); };
    const onRefresh = () => refresh();
    const onCount = (event: Event) => { const count = Number((event as CustomEvent).detail?.count); if (Number.isFinite(count) && count >= 0) setUnreadMessages(count); };
    refresh();
    const interval = window.setInterval(refresh, 60_000);
    window.addEventListener('admin:messages-unread-refresh', onRefresh);
    window.addEventListener('admin:messages-unread-count', onCount);
    return () => { cancelled = true; window.clearInterval(interval); window.removeEventListener('admin:messages-unread-refresh', onRefresh); window.removeEventListener('admin:messages-unread-count', onCount); };
  }, [token]);

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
    <div className={`admin-shell ${sidebarCollapsed ? 'is-sidebar-collapsed' : ''}`}>
      <header className="admin-topbar">
        <Tooltip content={menuOpen ? 'Затвори менюто' : 'Отвори менюто'}>
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
        </Tooltip>
        <p className="brand">Borz33</p>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          onClick={() => {
            toast.info('Излязохте от профила.');
            void dispatch(logout());
          }}
        >
          <LogOut />
          Изход
        </Button>
      </header>

      {menuOpen ? (
        <button type="button" className="nav-backdrop" aria-label="Затвори менюто" onClick={() => setMenuOpen(false)} />
      ) : null}

      <button type="button" className="sidebar-desktop-toggle" title={sidebarCollapsed ? 'Разгъни страничната лента' : 'Прибери страничната лента'} aria-label={sidebarCollapsed ? 'Разгъни страничната лента' : 'Прибери страничната лента'} aria-expanded={!sidebarCollapsed} aria-controls={menuId} onClick={toggleSidebar}>
        {sidebarCollapsed ? <PanelLeftOpen aria-hidden /> : <PanelLeftClose aria-hidden />}
      </button>

      <aside id={menuId} className={`admin-sidebar ${menuOpen ? 'is-open' : ''}`}>
        <Link to={routes.home} className="sidebar-brand">
          Borz33
        </Link>
        <nav className="side-nav" aria-label="Основна навигация">
          {navSections.map((section) => (
            <section key={section.label} className="nav-section" aria-labelledby={`nav-section-${section.label}`}>
              <h2 id={`nav-section-${section.label}`} className="nav-section-title">{section.label}</h2>
              {section.items.map((item) => (
                <NavLink key={item.to} to={item.to} end={item.to === '/'} className="nav-link">
                  <span>{item.label}</span>
                  {item.to === routes.messages && unreadMessages > 0 ? <span className="sidebar-unread-badge" aria-label={`${unreadMessages} непрочетени съобщения`}>{unreadMessages > 99 ? '99+' : unreadMessages}</span> : null}
                  {item.to === routes.notifications && unreadNotifications > 0 ? <span className="sidebar-unread-badge" aria-label={`${unreadNotifications} непрочетени известия`}>{unreadNotifications > 99 ? '99+' : unreadNotifications}</span> : null}
                </NavLink>
              ))}
            </section>
          ))}
        </nav>
        <div className="sidebar-user">
          {user ? (
            <Link
              to={`/users/${user.id}`}
              className="sidebar-user-row"
              aria-label={`Профил · ${displayName}`}
            >
              <div className="sidebar-user-avatar">
                {user.avatar_url ? (
                  <img src={user.avatar_url} alt="" className="size-full object-cover" />
                ) : (
                  <span className="sidebar-user-avatar-fallback">{displayName.slice(0, 1)}</span>
                )}
              </div>
              <p className="sidebar-user-name">{displayName}</p>
            </Link>
          ) : (
            <div className="sidebar-user-row">
              <div className="sidebar-user-avatar">
                <span className="sidebar-user-avatar-fallback">{displayName.slice(0, 1)}</span>
              </div>
              <p className="sidebar-user-name">{displayName}</p>
            </div>
          )}
          <Button
            type="button"
            variant="ghost"
            size="sm"
            className="w-full justify-start px-0"
            onClick={() => {
              toast.info('Излязохте от профила.');
              void dispatch(logout());
            }}
          >
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
      <AdminAssistant />
    </div>
  );
}
