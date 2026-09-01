import { useEffect } from 'react';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { routes } from '@/app/constants';
import { GuestOnly, RequireAuth } from '@/app/guards';
import { useAppDispatch } from '@/app/hooks';
import { useFormSaveShortcut } from '@/hooks/useFormSaveShortcut';
import { hydrateSession } from '@/features/auth/authThunks';
import { ThemeProvider } from '@/components/theme-provider';
import { LoadingProvider } from '@/components/loading-provider';
import { Toaster } from '@/components/ui/Toaster';
import { VoiceDictationProvider } from '@/components/voice-dictation-provider';
import { BannerFormPage } from '@/pages/BannerFormPage';
import { BannersPage } from '@/pages/BannersPage';
import { CategoriesPage } from '@/pages/CategoriesPage';
import { CategoryFormPage } from '@/pages/CategoryFormPage';
import { ComingSoonPage } from '@/pages/ComingSoonPage';
import { DashboardPage } from '@/pages/DashboardPage';
import { MediaPage } from '@/pages/MediaPage';
import { MessageDetailsPage } from '@/pages/MessageDetailsPage';
import { MessagesPage } from '@/pages/MessagesPage';
import { OrderDetailsPage } from '@/pages/OrderDetailsPage';
import { OrdersPage } from '@/pages/OrdersPage';
import { InvoicesPage } from '@/pages/InvoicesPage';
import { InvoiceDetailsPage } from '@/pages/InvoiceDetailsPage';
import { PageFormPage } from '@/pages/PageFormPage';
import { PagesPage } from '@/pages/PagesPage';
import { ProductsPage } from '@/pages/ProductsPage';
import { ProductEditPage } from '@/pages/ProductEditPage';
import { ProductViewPage } from '@/pages/ProductViewPage';
import { ProductTemplatesPage } from '@/pages/ProductTemplatesPage';
import { SettingsPage } from '@/pages/SettingsPage';
import { CustomizationPage } from '@/pages/CustomizationPage';
import { ForgotPasswordPage } from '@/pages/ForgotPasswordPage';
import { LoginPage } from '@/pages/LoginPage';
import { ResetPasswordPage } from '@/pages/ResetPasswordPage';
import { ReportsPage } from '@/pages/ReportsPage';
import { AccountingPage } from '@/pages/AccountingPage';
import { AccountingGuidePage } from '@/pages/AccountingGuidePage';
import { UserFormPage } from '@/pages/UserFormPage';
import { UsersPage } from '@/pages/UsersPage';

export function App() {
  const dispatch = useAppDispatch();
  useFormSaveShortcut();

  useEffect(() => {
    void dispatch(hydrateSession());
  }, [dispatch]);

  return (
    <ThemeProvider defaultTheme="system">
      <LoadingProvider>
        <Toaster />
        <VoiceDictationProvider />
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
              <Route path={routes.ordersShow} element={<OrderDetailsPage />} />
              <Route path={routes.orders} element={<OrdersPage />} />
              <Route path={routes.invoicesShow} element={<InvoiceDetailsPage />} />
              <Route path={routes.creditNotesShow} element={<InvoiceDetailsPage />} />
              <Route path={routes.creditNotes} element={<InvoicesPage documentType="credit_note" />} />
              <Route path={routes.invoices} element={<InvoicesPage documentType="invoice" />} />
              <Route path={routes.productsNew} element={<ProductEditPage />} />
              <Route path={routes.productTemplates} element={<ProductTemplatesPage />} />
              <Route path={routes.productsEdit} element={<ProductEditPage />} />
              <Route path={routes.productsShow} element={<ProductViewPage />} />
              <Route path={routes.products} element={<ProductsPage />} />
              <Route path={routes.categoriesNew} element={<CategoryFormPage />} />
              <Route path={routes.categoriesEdit} element={<CategoryFormPage />} />
              <Route path={routes.categories} element={<CategoriesPage />} />
              <Route path={routes.media} element={<MediaPage />} />
              <Route path={routes.pagesNew} element={<PageFormPage />} />
              <Route path={routes.pagesEdit} element={<PageFormPage />} />
              <Route path={routes.pages} element={<PagesPage />} />
              <Route path={routes.bannersNew} element={<BannerFormPage />} />
              <Route path={routes.bannersEdit} element={<BannerFormPage />} />
              <Route path={routes.banners} element={<BannersPage />} />
              <Route path={routes.content} element={<Navigate to={routes.pages} replace />} />
              <Route path={routes.campaigns} element={<ComingSoonPage />} />
              <Route path={routes.shipments} element={<ComingSoonPage />} />
              <Route path={routes.messagesShow} element={<MessageDetailsPage />} />
              <Route path={routes.messages} element={<MessagesPage />} />
              <Route path={routes.reports} element={<ReportsPage />} />
              <Route path={routes.accounting} element={<AccountingPage />} />
              <Route path={routes.accountingGuide} element={<AccountingGuidePage />} />
              <Route path={routes.settings} element={<SettingsPage />} />
              <Route path={routes.customization} element={<CustomizationPage />} />
            </Route>
            <Route path="*" element={<Navigate to={routes.login} replace />} />
          </Routes>
        </BrowserRouter>
      </LoadingProvider>
    </ThemeProvider>
  );
}
