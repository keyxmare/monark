import type { RouteRecordRaw } from 'vue-router'

export const activityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/activity/pages/DashboardPage.vue'),
    meta: { layout: 'dashboard' },
    name: 'dashboard',
    path: '/',
  },
]
