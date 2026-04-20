import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/hub/shared/types/enums';

export const identityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/hub/identity/pages/LoginPage.vue'),
    meta: { layout: Layout.Auth, public: true },
    name: 'login',
    path: '/login',
  },
  {
    component: () => import('@/hub/identity/pages/RegisterPage.vue'),
    meta: { layout: Layout.Auth, public: true },
    name: 'register',
    path: '/register',
  },
  {
    component: () => import('@/hub/identity/pages/ProfilePage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'profile',
    path: '/profile',
  },
  {
    component: () => import('@/hub/identity/pages/UserList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'identity-users-list',
    path: '/identity/users',
  },
  {
    component: () => import('@/hub/identity/pages/UserDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'identity-users-detail',
    path: '/identity/users/:id',
  },
];
