import { expect, test } from '@playwright/test';
import { LoginPage } from '../pages/login.page';

test.describe('Authentication', () => {
  test('shows login form', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await expect(loginPage.form).toBeVisible();
  });

  test('redirects unauthenticated users to login', async ({ page }) => {
    await page.goto('/catalog/projects');
    await expect(page).toHaveURL(/\/login/);
  });

  test('shows error on invalid credentials', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login('bad@example.com', 'wrongpassword');
    await expect(loginPage.errorMessage).toBeVisible();
  });

  test('successful login redirects to dashboard', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(
      process.env.E2E_USER_EMAIL ?? 'admin@monark.dev',
      process.env.E2E_USER_PASSWORD ?? 'password',
    );
    await expect(page).toHaveURL('/');
  });
});
