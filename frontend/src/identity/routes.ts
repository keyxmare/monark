import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const identityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/identity/pages/LoginPage.vue'),
    meta: { layout: Layout.Auth, public: true },
    name: 'login',
    path: '/login',
  },
  {
    component: () => import('@/identity/pages/RegisterPage.vue'),
    meta: { layout: Layout.Auth, public: true },
    name: 'register',
    path: '/register',
  },
  {
    component: () => import('@/identity/pages/ProfilePage.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'profile',
    path: '/profile',
  },
  {
    component: () => import('@/identity/pages/UserList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'identity-users-list',
    path: '/identity/users',
  },
  {
    component: () => import('@/identity/pages/UserDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'identity-users-detail',
    path: '/identity/users/:id',
  },
];
