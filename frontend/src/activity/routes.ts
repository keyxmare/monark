import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const activityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/activity/pages/DashboardPage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dashboard',
    path: '/',
  },
];
