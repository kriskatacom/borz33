# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: accounting.spec.ts >> Администрация – Счетоводство >> показва или скрива ДДС и Econt според настройките
- Location: specs/accounting.spec.ts:29:3

# Error details

```
Error: expect(locator).toBeHidden() failed

Locator:  getByRole('heading', { name: 'Econt сверяване' })
Expected: hidden
Received: visible
Timeout:  5000ms

Call log:
  - Expect "toBeHidden" with timeout 5000ms
  - waiting for getByRole('heading', { name: 'Econt сверяване' })
    14 × locator resolved to <h2>Econt сверяване</h2>
       - unexpected value "visible"

```

```yaml
- heading "Econt сверяване" [level=2]
```

# Test source

```ts
  1  | import { expect, test } from '@playwright/test';
  2  | 
  3  | test.describe('Администрация – Счетоводство', () => {
  4  |   test.beforeEach(async ({ page }) => {
  5  |     const token = process.env.ADMIN_AUTH_TOKEN;
  6  |     if (!process.env.ADMIN_STORAGE_STATE && !token) {
  7  |       throw new Error('Задайте ADMIN_STORAGE_STATE или ADMIN_AUTH_TOKEN за authenticated E2E тестове.');
  8  |     }
  9  |     if (token) {
  10 |       await page.addInitScript((authToken) => {
  11 |         window.localStorage.setItem('borz33.admin.token', authToken);
  12 |       }, token);
  13 |     }
  14 |     await page.goto('/accounting');
  15 |     await expect(page.getByRole('heading', { name: 'Счетоводство' })).toBeVisible({ timeout: 15_000 });
  16 |   });
  17 | 
  18 |   test('показва филтрите и сменя вида справка', async ({ page }) => {
  19 |     for (const label of ['От дата', 'До дата', 'Статус на поръчка', 'Фактуриране']) {
  20 |       await expect(page.getByText(label, { exact: true }).first()).toBeVisible();
  21 |     }
  22 |     await expect(page.locator('#acc-paid')).toBeVisible();
  23 |     await page.locator('#report-type').click();
  24 |     await expect(page.getByRole('option', { name: 'Продажби' })).toBeVisible();
  25 |     await page.getByRole('option', { name: 'Фактури' }).click();
  26 |     await expect(page.locator('table')).toBeVisible();
  27 |   });
  28 | 
  29 |   test('показва или скрива ДДС и Econt според настройките', async ({ page }) => {
  30 |     const vat = page.getByText('Начислено ДДС', { exact: true });
  31 |     const econt = page.getByRole('heading', { name: 'Econt сверяване' });
  32 |     if (process.env.ACCOUNTING_VAT_ENABLED === 'true') await expect(vat).toBeVisible();
  33 |     if (process.env.ACCOUNTING_VAT_ENABLED === 'false') await expect(vat).toBeHidden();
  34 |     if (process.env.ACCOUNTING_ECONT_ENABLED === 'true') await expect(econt).toBeVisible();
> 35 |     if (process.env.ACCOUNTING_ECONT_ENABLED === 'false') await expect(econt).toBeHidden();
     |                                                                               ^ Error: expect(locator).toBeHidden() failed
  36 |   });
  37 | 
  38 |   test('показва празно състояние и периодна политика', async ({ page }) => {
  39 |     await expect(page.locator('#acc-from')).toBeVisible();
  40 |     await expect(page.locator('#acc-to')).toBeVisible();
  41 |     await expect(page.locator('#acc-from')).toHaveAttribute('id', 'acc-from');
  42 |   });
  43 | 
  44 |   test('показва приключването и audit log', async ({ page }) => {
  45 |     await expect(page.getByRole('heading', { name: 'Месечно счетоводно приключване' })).toBeVisible();
  46 |     await expect(page.getByRole('button', { name: 'Изберете месец за отчета' })).toBeVisible();
  47 |     await expect(page.getByRole('heading', { name: 'Audit log' })).toBeVisible();
  48 |   });
  49 | 
  50 |   test('изпраща текущите филтри към API', async ({ page }) => {
  51 |     const requests: string[] = [];
  52 |     page.on('request', request => { if (request.url().includes('/admin/accounting/')) requests.push(request.url()); });
  53 |     await page.getByRole('button', { name: 'Обнови' }).click();
  54 |     await page.waitForTimeout(250);
  55 |     expect(requests.some(url => url.includes('date_from=') && url.includes('date_to='))).toBeTruthy();
  56 |   });
  57 | });
  58 | 
```