import type { RouteRecordRaw } from 'vue-router';

import { identityRoutes } from '@/hub/identity/routes';
import { Layout } from '@/hub/shared/types/enums';

const homeRoute: RouteRecordRaw = {
  component: () => import('@/hub/pages/HubHomePage.vue'),
  meta: { layout: Layout.Dashboard },
  name: 'hub-home',
  path: '/',
};

export const hubRoutes: RouteRecordRaw[] = [homeRoute, ...identityRoutes];
