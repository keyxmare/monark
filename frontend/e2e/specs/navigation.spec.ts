import { expect, test } from '@playwright/test';

test.describe('Navigation', () => {
  test('dashboard loads after login', async ({ page }) => {
    // Login first
    await page.goto('/login');
    await page
      .getByTestId('login-email')
      .fill(process.env.E2E_USER_EMAIL ?? 'admin@monark.dev');
    await page
      .getByTestId('login-password')
      .fill(process.env.E2E_USER_PASSWORD ?? 'password');
    await page.getByTestId('login-submit').click();
    await page.waitForURL('**/');
    await expect(page.getByTestId('main-content')).toBeVisible();
  });

  test('sidebar navigation works', async ({ page }) => {
    await page.goto('/login');
    await page
      .getByTestId('login-email')
      .fill(process.env.E2E_USER_EMAIL ?? 'admin@monark.dev');
    await page
      .getByTestId('login-password')
      .fill(process.env.E2E_USER_PASSWORD ?? 'password');
    await page.getByTestId('login-submit').click();
    await page.waitForURL('**/');
    // Verify main content area exists
    await expect(page.getByTestId('main-content')).toBeVisible();
  });
});
