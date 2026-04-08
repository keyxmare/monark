import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createRouter, createWebHistory } from 'vue-router';

import { STORAGE_KEYS } from '@/shared/constants';

function buildRouter() {
  const r = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'dashboard', component: { template: '<div />' } },
      {
        path: '/login',
        name: 'login',
        component: { template: '<div />' },
        meta: { public: true },
      },
    ],
  });

  r.beforeEach((to) => {
    const isAuthenticated = !!localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
    const isPublicRoute = to.meta.public === true;

    if (!isAuthenticated && !isPublicRoute) {
      return { name: 'login' };
    }

    if (isAuthenticated && to.name === 'login') {
      return { name: 'dashboard' };
    }
  });

  return r;
}

describe('router navigation guards', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('redirects unauthenticated users to login for protected routes', async () => {
    const router = buildRouter();

    await router.push('/');
    await router.isReady();

    expect(router.currentRoute.value.name).toBe('login');
  });

  it('allows unauthenticated users to access public routes', async () => {
    const router = buildRouter();

    await router.push('/login');
    await router.isReady();

    expect(router.currentRoute.value.name).toBe('login');
  });

  it('redirects authenticated users from login to dashboard', async () => {
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, 'test-token');
    const router = buildRouter();

    await router.push('/login');
    await router.isReady();

    expect(router.currentRoute.value.name).toBe('dashboard');
  });

  it('allows authenticated users to access protected routes', async () => {
    localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, 'test-token');
    const router = buildRouter();

    await router.push('/');
    await router.isReady();

    expect(router.currentRoute.value.name).toBe('dashboard');
  });
});
