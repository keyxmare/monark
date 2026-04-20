import { createRouter, createWebHistory } from 'vue-router';

import { monitoringRoutes } from '@/apps/monitoring/routes';
import { hubRoutes } from '@/hub/routes';
import { STORAGE_KEYS } from '@/hub/shared/constants';

const routes = [...hubRoutes, ...monitoringRoutes];

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
    return { name: 'hub-home' };
  }
});
