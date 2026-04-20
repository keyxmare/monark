import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/hub/shared/types/enums';

export const activityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/apps/monitoring/activity/pages/DashboardPage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dashboard',
    path: '/monitoring/dashboard',
  },
];
