import { useEffect } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { routes } from '@/app/constants';
import { GuestOnly, RequireAuth } from '@/app/guards';
import { useAppDispatch } from '@/app/hooks';
import { hydrateSession } from '@/features/auth/authThunks';
import { ComingSoonPage } from '@/pages/ComingSoonPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { LoginPage } from '@/pages/LoginPage';
import { ResetPasswordPage } from '@/pages/ResetPasswordPage';

export function App() {
  const dispatch = useAppDispatch();

  useEffect(() => {
    void dispatch(hydrateSession());
  }, [dispatch]);

  return (
    <BrowserRouter>
      <Routes>
        <Route element={<GuestOnly />}>
          <Route path={routes.login} element={<LoginPage />} />
          <Route path={routes.forgotPassword} element={<ForgotPasswordPage />} />
          <Route path={routes.resetPassword} element={<ResetPasswordPage />} />
        </Route>
        <Route element={<RequireAuth />}>
          <Route path={routes.home} element={<DashboardPage />} />
          <Route path={routes.orders} element={<ComingSoonPage />} />
          <Route path={routes.products} element={<ComingSoonPage />} />
          <Route path={routes.customers} element={<ComingSoonPage />} />
          <Route path={routes.content} element={<ComingSoonPage />} />
          <Route path={routes.campaigns} element={<ComingSoonPage />} />
          <Route path={routes.shipments} element={<ComingSoonPage />} />
          <Route path={routes.messages} element={<ComingSoonPage />} />
          <Route path={routes.reports} element={<ComingSoonPage />} />
          <Route path={routes.settings} element={<ComingSoonPage />} />
        </Route>
        <Route path="*" element={<Navigate to={routes.login} replace />} />
      </Routes>
    </BrowserRouter>
  );
}
