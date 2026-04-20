import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/hub/shared/types/enums';

export const coverageRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/apps/monitoring/coverage/pages/CoverageDashboard.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'coverage-dashboard',
    path: '/monitoring/coverage',
  },
  {
    component: () => import('@/apps/monitoring/coverage/pages/CoverageDashboard.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'coverage-project',
    path: '/monitoring/coverage/:slug',
  },
];
