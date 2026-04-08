import { expect, test } from '@playwright/test';

test.describe('Projects', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByTestId('login-email').fill(process.env.E2E_USER_EMAIL ?? 'admin@monark.dev');
    await page.getByTestId('login-password').fill(process.env.E2E_USER_PASSWORD ?? 'password');
    await page.getByTestId('login-submit').click();
    await page.waitForURL('**/');
  });

  test('project list page loads', async ({ page }) => {
    await page.goto('/catalog/projects');
    await page.waitForLoadState('networkidle');
    await expect(page.getByTestId('project-list-page')).toBeVisible();
  });

  test('provider list page loads', async ({ page }) => {
    await page.goto('/catalog/providers');
    await page.waitForLoadState('networkidle');
    await expect(page.getByTestId('provider-list-page')).toBeVisible();
  });
});
