import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const historyRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/history/pages/ProjectHistoryPage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'history-project-timeline',
    path: '/projects/:id/history',
  },
];
