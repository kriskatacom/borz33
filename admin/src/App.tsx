import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { routes } from '@/app/constants';
import { GuestOnly, RequireAuth } from '@/app/guards';
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { HomePage } from '@/pages/HomePage';
import { LoginPage } from '@/pages/LoginPage';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route element={<GuestOnly />}>
          <Route path={routes.login} element={<LoginPage />} />
          <Route path={routes.forgotPassword} element={<ForgotPasswordPage />} />
        </Route>
        <Route element={<RequireAuth />}>
          <Route path={routes.home} element={<HomePage />} />
        </Route>
        <Route path="*" element={<Navigate to={routes.login} replace />} />
      </Routes>
    </BrowserRouter>
  );
}
