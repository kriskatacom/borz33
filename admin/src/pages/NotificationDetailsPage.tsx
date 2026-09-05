import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ArrowLeft, Bell, Box, CircleAlert, ExternalLink, Info, TriangleAlert } from 'lucide-react';
import { listNotifications, setNotificationRead, type AdminNotification } from '@/api/notifications';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { AdminPageSkeleton } from '@/components/admin-page-skeleton';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { formatDateTime } from '@/lib/format';

const icons = { info: Info, warning: TriangleAlert, critical: CircleAlert };

export function NotificationDetailsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const [item, setItem] = useState<AdminNotification | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    if (!Number.isInteger(id) || id < 1) {
      navigate(routes.notifications, { replace: true });
      return;
    }

    setError(null);
    void listNotifications(token)
      .then((response) => {
        const notification = response.data?.notifications?.find((candidate) => candidate.id === id);
        if (!notification) throw new Error('Известието не е намерено.');
        if (cancelled) return;
        setItem(notification);
        if (notification.read_at !== null) {
          return;
        }

        void setNotificationRead(token, notification.id, true)
          .then((markResponse) => {
            if (cancelled) return;
            setItem(markResponse.data.notification);
            window.dispatchEvent(new Event('admin:notifications-refresh'));
          })
          .catch(() => undefined);
      })
      .catch((error) => {
        if (!cancelled) {
          setError(error instanceof Error ? error.message : 'Известието не можа да се зареди.');
        }
      });

    return () => { cancelled = true; };
  }, [id, navigate, token]);

  if (error) return <div className="page"><PageHeader title="Известие" crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Известия', to: routes.notifications }, { label: 'Грешка' }]} /><div className="notification-empty"><Bell /><h2>Известието не може да бъде заредено</h2><p>{error}</p><Button asChild variant="outline"><Link to={routes.notifications}><ArrowLeft />Към известията</Link></Button></div></div>;
  if (!item) return <div className="page"><PageHeader title="Известие" crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Известия', to: routes.notifications }, { label: 'Зареждане' }]} /><AdminPageSkeleton sections={2} /></div>;
  const Icon = icons[item.level] ?? Bell;
  const metadata = item.metadata ?? {};
  const image = metadata.image_url;
  const stock = metadata.stock;
  return <div className="page"><PageHeader title="Детайли за известие" help="Пълна информация за настъпилото събитие." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Известия', to: routes.notifications }, { label: item.title }]} actions={<Button asChild variant="outline"><Link to={routes.notifications}><ArrowLeft />Към известията</Link></Button>} /><article className="notification-detail-card"><header><span className={`notification-icon is-${item.level}`}><Icon /></span><div><p>Известие за магазин</p><h2>{item.title}</h2><time>{item.created_at ? formatDateTime(item.created_at) : ''}</time></div></header><div className="notification-detail-body">{image ? <img className="notification-detail-image" src={image} alt="" /> : null}<div><h3>Какво се случи</h3><p>{item.body}</p>{typeof stock === 'number' ? <div className="notification-stock"><Box />Текуща наличност: <strong>{stock} бр.</strong></div> : null}</div></div>{item.link ? <footer><Button asChild><Link to={item.link}><ExternalLink />Отвори свързания продукт</Link></Button></footer> : null}</article></div>;
}
