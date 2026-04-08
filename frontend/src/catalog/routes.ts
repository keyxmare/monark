import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/shared/types/enums';

export const catalogRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/catalog/pages/ProviderList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-list',
    path: '/catalog/providers',
  },
  {
    component: () => import('@/catalog/pages/ProviderForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-create',
    path: '/catalog/providers/new',
  },
  {
    component: () => import('@/catalog/pages/ProviderDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-detail',
    path: '/catalog/providers/:id',
  },
  {
    component: () => import('@/catalog/pages/ProviderForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-edit',
    path: '/catalog/providers/:id/edit',
  },
  {
    component: () => import('@/catalog/pages/ProjectList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-projects-list',
    path: '/catalog/projects',
  },
  {
    component: () => import('@/catalog/pages/ProjectDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-projects-detail',
    path: '/catalog/projects/:id',
  },
  {
    component: () => import('@/catalog/pages/FrameworkList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-frameworks-list',
    path: '/catalog/frameworks',
  },
];
