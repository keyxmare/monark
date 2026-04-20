import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/hub/shared/types/enums';

export const dependencyRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/apps/monitoring/dependency/pages/DependencyList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-dependencies-list',
    path: '/monitoring/dependency/dependencies',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/DependencyForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-dependencies-create',
    path: '/monitoring/dependency/dependencies/new',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/DependencyDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-dependencies-detail',
    path: '/monitoring/dependency/dependencies/:id',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/DependencyForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-dependencies-edit',
    path: '/monitoring/dependency/dependencies/:id/edit',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/VulnerabilityList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-vulnerabilities-list',
    path: '/monitoring/dependency/vulnerabilities',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/VulnerabilityForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-vulnerabilities-create',
    path: '/monitoring/dependency/vulnerabilities/new',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/VulnerabilityDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-vulnerabilities-detail',
    path: '/monitoring/dependency/vulnerabilities/:id',
  },
  {
    component: () => import('@/apps/monitoring/dependency/pages/VulnerabilityForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'dependency-vulnerabilities-edit',
    path: '/monitoring/dependency/vulnerabilities/:id/edit',
  },
];
