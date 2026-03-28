import type { RouteRecordRaw } from 'vue-router';

export const identityRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/identity/pages/LoginPage.vue'),
    meta: { layout: 'auth', public: true },
    name: 'login',
    path: '/login',
  },
  {
    component: () => import('@/identity/pages/RegisterPage.vue'),
    meta: { layout: 'auth', public: true },
    name: 'register',
    path: '/register',
  },
  {
    component: () => import('@/identity/pages/ProfilePage.vue'),
    meta: { layout: 'dashboard' },
    name: 'profile',
    path: '/profile',
  },
  {
    component: () => import('@/identity/pages/UserList.vue'),
    meta: { layout: 'dashboard' },
    name: 'identity-users-list',
    path: '/identity/users',
  },
  {
    component: () => import('@/identity/pages/UserDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'identity-users-detail',
    path: '/identity/users/:id',
  },
  {
    component: () => import('@/identity/pages/AccessTokenList.vue'),
    meta: { layout: 'dashboard' },
    name: 'identity-access-tokens-list',
    path: '/identity/access-tokens',
  },
  {
    component: () => import('@/identity/pages/AccessTokenForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'identity-access-tokens-create',
    path: '/identity/access-tokens/create',
  },
];
