import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const coverageRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/coverage/pages/CoverageDashboard.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'coverage-dashboard',
    path: '/coverage',
  },
  {
    component: () => import('@/coverage/pages/CoverageDashboard.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'coverage-project',
    path: '/coverage/:slug',
  },
];
