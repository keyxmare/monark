import { createRouter, createWebHistory } from 'vue-router'

import { activityRoutes } from '@/activity/routes'
import { identityRoutes } from '@/identity/routes'

const routes = [...activityRoutes, ...identityRoutes]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const isAuthenticated = !!localStorage.getItem('auth_token')
  const isPublicRoute = to.meta.public === true

  if (!isAuthenticated && !isPublicRoute) {
    return { name: 'login' }
  }

  if (isAuthenticated && to.name === 'login') {
    return { name: 'dashboard' }
  }
})
