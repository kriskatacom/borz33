import { Navigate, Outlet } from 'react-router-dom';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { AdminLayout } from '@/layouts/AdminLayout';

function Splash() {
  return (
    <div className="splash" role="status">
      Зареждане…
    </div>
  );
}

export function GuestOnly() {
  const { status, token } = useAppSelector((state) => state.auth);

  if (status === 'hydrating') {
    return <Splash />;
  }

  if (token) {
    return <Navigate to={routes.home} replace />;
  }

  return <Outlet />;
}

export function RequireAuth() {
  const { status, token } = useAppSelector((state) => state.auth);

  if (status === 'hydrating') {
    return <Splash />;
  }

  if (!token) {
    return <Navigate to={routes.login} replace />;
  }

  return (
    <AdminLayout>
      <Outlet />
    </AdminLayout>
  );
}
