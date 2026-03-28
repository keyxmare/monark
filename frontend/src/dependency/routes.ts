import type { RouteRecordRaw } from 'vue-router';

export const dependencyRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/dependency/pages/DependencyList.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-dependencies-list',
    path: '/dependency/dependencies',
  },
  {
    component: () => import('@/dependency/pages/DependencyForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-dependencies-create',
    path: '/dependency/dependencies/new',
  },
  {
    component: () => import('@/dependency/pages/DependencyDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-dependencies-detail',
    path: '/dependency/dependencies/:id',
  },
  {
    component: () => import('@/dependency/pages/DependencyForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-dependencies-edit',
    path: '/dependency/dependencies/:id/edit',
  },
  {
    component: () => import('@/dependency/pages/VulnerabilityList.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-vulnerabilities-list',
    path: '/dependency/vulnerabilities',
  },
  {
    component: () => import('@/dependency/pages/VulnerabilityForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-vulnerabilities-create',
    path: '/dependency/vulnerabilities/new',
  },
  {
    component: () => import('@/dependency/pages/VulnerabilityDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-vulnerabilities-detail',
    path: '/dependency/vulnerabilities/:id',
  },
  {
    component: () => import('@/dependency/pages/VulnerabilityForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'dependency-vulnerabilities-edit',
    path: '/dependency/vulnerabilities/:id/edit',
  },
];
