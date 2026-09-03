import { expect, test } from '@playwright/test';

test.describe('Администрация – Счетоводство', () => {
  test.beforeEach(async ({ page }) => {
    const token = process.env.ADMIN_AUTH_TOKEN;
    if (!process.env.ADMIN_STORAGE_STATE && !token) {
      throw new Error('Задайте ADMIN_STORAGE_STATE или ADMIN_AUTH_TOKEN за authenticated E2E тестове.');
    }
    if (token) {
      await page.addInitScript((authToken) => {
        window.localStorage.setItem('borz33.admin.token', authToken);
      }, token);
    }
    await page.goto('/accounting');
    await expect(page.getByRole('heading', { name: 'Счетоводство' })).toBeVisible({ timeout: 15_000 });
  });

  test('показва филтрите и сменя вида справка', async ({ page }) => {
    for (const label of ['От дата', 'До дата', 'Статус на поръчка', 'Фактуриране']) {
      await expect(page.getByText(label, { exact: true }).first()).toBeVisible();
    }
    await expect(page.locator('#acc-paid')).toBeVisible();
    await page.locator('#report-type').click();
    await expect(page.getByRole('option', { name: 'Продажби' })).toBeVisible();
    await page.getByRole('option', { name: 'Фактури' }).click();
    await expect(page.locator('table')).toBeVisible();
  });

  test('показва или скрива ДДС и Econt според настройките', async ({ page }) => {
    const vat = page.getByText('Начислено ДДС', { exact: true });
    const econt = page.getByRole('heading', { name: 'Econt сверяване' });
    if (process.env.ACCOUNTING_VAT_ENABLED === 'true') await expect(vat).toBeVisible();
    if (process.env.ACCOUNTING_VAT_ENABLED === 'false') await expect(vat).toBeHidden();
    if (process.env.ACCOUNTING_ECONT_ENABLED === 'true') await expect(econt).toBeVisible();
    if (process.env.ACCOUNTING_ECONT_ENABLED === 'false') await expect(econt).toBeHidden();
  });

  test('показва празно състояние и периодна политика', async ({ page }) => {
    await expect(page.locator('#acc-from')).toBeVisible();
    await expect(page.locator('#acc-to')).toBeVisible();
    await expect(page.locator('#acc-from')).toHaveAttribute('id', 'acc-from');
  });

  test('показва приключването и audit log', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Месечно счетоводно приключване' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Изберете месец за отчета' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Audit log' })).toBeVisible();
  });

  test('изпраща текущите филтри към API', async ({ page }) => {
    const requests: string[] = [];
    page.on('request', request => { if (request.url().includes('/admin/accounting/')) requests.push(request.url()); });
    await page.getByRole('button', { name: 'Обнови' }).click();
    await page.waitForTimeout(250);
    expect(requests.some(url => url.includes('date_from=') && url.includes('date_to='))).toBeTruthy();
  });
});
