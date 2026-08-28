import { useEffect } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { routes } from '@/app/constants';
import { GuestOnly, RequireAuth } from '@/app/guards';
import { useAppDispatch } from '@/app/hooks';
import { hydrateSession } from '@/features/auth/authThunks';
import { ThemeProvider } from '@/components/theme-provider';
import { LoadingProvider } from '@/components/loading-provider';
import { Toaster } from '@/components/ui/Toaster';
import { ComingSoonPage } from '@/pages/ComingSoonPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { MediaPage } from '@/pages/MediaPage';
import { ProductsPage } from '@/pages/ProductsPage';
import { ProductEditPage } from '@/pages/ProductEditPage';
import { ProductViewPage } from '@/pages/ProductViewPage';
import { SettingsPage } from '@/pages/SettingsPage';
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { LoginPage } from '@/pages/LoginPage';
import { ResetPasswordPage } from '@/pages/ResetPasswordPage';
import { UserFormPage } from '@/pages/UserFormPage';
import { UsersPage } from '@/pages/UsersPage';

export function App() {
  const dispatch = useAppDispatch();

  useEffect(() => {
    void dispatch(hydrateSession());
  }, [dispatch]);

  return (
    <ThemeProvider defaultTheme="system">
      <LoadingProvider>
        <Toaster />
        <BrowserRouter>
          <Routes>
            <Route element={<GuestOnly />}>
              <Route path={routes.login} element={<LoginPage />} />
              <Route path={routes.forgotPassword} element={<ForgotPasswordPage />} />
              <Route path={routes.resetPassword} element={<ResetPasswordPage />} />
            </Route>
            <Route element={<RequireAuth />}>
              <Route path={routes.home} element={<DashboardPage />} />
              <Route path={routes.usersNew} element={<UserFormPage />} />
              <Route path={routes.usersEdit} element={<UserFormPage />} />
              <Route path={routes.users} element={<UsersPage />} />
              <Route path={routes.customers} element={<Navigate to={routes.users} replace />} />
              <Route path={routes.orders} element={<ComingSoonPage />} />
              <Route path={routes.productsEdit} element={<ProductEditPage />} />
              <Route path={routes.productsShow} element={<ProductViewPage />} />
              <Route path={routes.products} element={<ProductsPage />} />
              <Route path={routes.media} element={<MediaPage />} />
              <Route path={routes.content} element={<ComingSoonPage />} />
              <Route path={routes.campaigns} element={<ComingSoonPage />} />
              <Route path={routes.shipments} element={<ComingSoonPage />} />
              <Route path={routes.messages} element={<ComingSoonPage />} />
              <Route path={routes.reports} element={<ComingSoonPage />} />
              <Route path={routes.settings} element={<SettingsPage />} />
            </Route>
            <Route path="*" element={<Navigate to={routes.login} replace />} />
          </Routes>
        </BrowserRouter>
      </LoadingProvider>
    </ThemeProvider>
  );
}
