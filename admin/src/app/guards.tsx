import { Navigate, Outlet } from 'react-router-dom';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';

export function GuestOnly() {
  const token = useAppSelector((state) => state.auth.token);

  if (token) {
    return <Navigate to={routes.home} replace />;
  }

  return <Outlet />;
}

export function RequireAuth() {
  const token = useAppSelector((state) => state.auth.token);

  if (!token) {
    return <Navigate to={routes.login} replace />;
  }

  return <Outlet />;
}
