import type { RouteRecordRaw } from 'vue-router'

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
]
