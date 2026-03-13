import type { RouteRecordRaw } from 'vue-router'

export const catalogRoutes: RouteRecordRaw[] = [
  {
    component: () => import('@/catalog/pages/ProviderList.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-providers-list',
    path: '/catalog/providers',
  },
  {
    component: () => import('@/catalog/pages/ProviderForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-providers-create',
    path: '/catalog/providers/new',
  },
  {
    component: () => import('@/catalog/pages/ProviderDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-providers-detail',
    path: '/catalog/providers/:id',
  },
  {
    component: () => import('@/catalog/pages/ProviderForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-providers-edit',
    path: '/catalog/providers/:id/edit',
  },
  {
    component: () => import('@/catalog/pages/ProjectList.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-projects-list',
    path: '/catalog/projects',
  },
  {
    component: () => import('@/catalog/pages/ProjectForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-projects-create',
    path: '/catalog/projects/new',
  },
  {
    component: () => import('@/catalog/pages/ProjectDetail.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-projects-detail',
    path: '/catalog/projects/:id',
  },
  {
    component: () => import('@/catalog/pages/ProjectForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-projects-edit',
    path: '/catalog/projects/:id/edit',
  },
  {
    component: () => import('@/catalog/pages/TechStackList.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-tech-stacks-list',
    path: '/catalog/tech-stacks',
  },
  {
    component: () => import('@/catalog/pages/TechStackForm.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-tech-stacks-create',
    path: '/catalog/tech-stacks/new',
  },
  {
    component: () => import('@/catalog/pages/MergeRequestList.vue'),
    meta: { layout: 'dashboard' },
    name: 'catalog-merge-requests-list',
    path: '/catalog/projects/:projectId/merge-requests',
  },
]
