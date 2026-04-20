import type { RouteRecordRaw } from 'vue-router';

import { Layout } from '@/hub/shared/types/enums';

export const catalogRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/apps/monitoring/catalog/pages/ProviderList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-list',
    path: '/monitoring/catalog/providers',
  },
  {
    component: () => import('@/apps/monitoring/catalog/pages/ProviderForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-create',
    path: '/monitoring/catalog/providers/new',
  },
  {
    component: () => import('@/apps/monitoring/catalog/pages/ProviderDetail.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-detail',
    path: '/monitoring/catalog/providers/:id',
  },
  {
    component: () => import('@/apps/monitoring/catalog/pages/ProviderForm.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-providers-edit',
    path: '/monitoring/catalog/providers/:id/edit',
  },
  {
    component: () => import('@/apps/monitoring/catalog/pages/ProjectList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-projects-list',
    path: '/monitoring/catalog/projects',
  },
  {
    component: () => import('@/apps/monitoring/catalog/pages/FrameworkList.vue'),
    meta: { layout: Layout.Dashboard },
    name: 'catalog-frameworks-list',
    path: '/monitoring/catalog/frameworks',
  },
];
