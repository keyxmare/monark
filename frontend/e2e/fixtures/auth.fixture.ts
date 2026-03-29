import { test as base } from '@playwright/test';

type AuthFixtures = {
  authenticatedPage: void;
};

export const test = base.extend<AuthFixtures>({
  authenticatedPage: [
    async ({ page }, use) => {
      await page.goto('/login');
      await page
        .getByTestId('login-email')
        .fill(process.env.E2E_USER_EMAIL ?? 'admin@monark.dev');
      await page
        .getByTestId('login-password')
        .fill(process.env.E2E_USER_PASSWORD ?? 'password');
      await page.getByTestId('login-submit').click();
      await page.waitForURL('**/');
      await use();
    },
    { auto: false },
  ],
});

export { expect } from '@playwright/test';
