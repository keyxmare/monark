import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const activityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/activity/pages/DashboardPage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dashboard',
    path: '/',
  },
  {
    component: () => import('@/activity/pages/ActivityEventList.vue'),
    name: 'activity-events-list',
    path: '/activity/events',
  },
  {
    component: () => import('@/activity/pages/ActivityEventDetail.vue'),
    name: 'activity-events-detail',
    path: '/activity/events/:id',
  },
  {
    component: () => import('@/activity/pages/NotificationList.vue'),
    name: 'activity-notifications-list',
    path: '/activity/notifications',
  },
  {
    component: () => import('@/activity/pages/NotificationDetail.vue'),
    name: 'activity-notifications-detail',
    path: '/activity/notifications/:id',
  },
  {
    component: () => import('@/activity/pages/SyncTaskList.vue'),
    name: 'activity-sync-tasks-list',
    path: '/activity/sync-tasks',
  },
  {
    component: () => import('@/activity/pages/MessengerMonitor.vue'),
    name: 'activity-messenger',
    path: '/activity/messenger',
  },
];
