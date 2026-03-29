import { createRouter, createWebHistory } from 'vue-router';

import { STORAGE_KEYS } from '@/shared/constants';
import { activityRoutes } from '@/activity/routes';
import { catalogRoutes } from '@/catalog/routes';
import { dependencyRoutes } from '@/dependency/routes';
import { identityRoutes } from '@/identity/routes';

const routes = [...activityRoutes, ...catalogRoutes, ...dependencyRoutes, ...identityRoutes];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  const isAuthenticated = !!localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN);
  const isPublicRoute = to.meta.public === true;

  if (!isAuthenticated && !isPublicRoute) {
    return { name: 'login' };
  }

  if (isAuthenticated && to.name === 'login') {
    return { name: 'dashboard' };
  }
});
