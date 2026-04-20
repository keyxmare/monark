import type { RouteRecordRaw } from 'vue-router';

import { activityRoutes } from '@/apps/monitoring/activity/routes';
import { catalogRoutes } from '@/apps/monitoring/catalog/routes';
import { coverageRoutes } from '@/apps/monitoring/coverage/routes';
import { dependencyRoutes } from '@/apps/monitoring/dependency/routes';

export const monitoringRoutes: RouteRecordRaw[] = [
  {
    path: '/monitoring',
    component: () => import('@/apps/monitoring/shell/MonitoringShell.vue'),
    meta: { app: 'monitoring' },
    children: [
      { path: '', redirect: '/monitoring/dashboard' },
      ...activityRoutes,
      ...catalogRoutes,
      ...coverageRoutes,
      ...dependencyRoutes,
    ],
  },
];
