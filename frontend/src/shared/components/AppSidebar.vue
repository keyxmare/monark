<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useSidebar } from '@/shared/composables/useSidebar'

const route = useRoute()
const { collapsed, mobileOpen, toggle } = useSidebar()

interface NavItem {
  icon: string
  label: string
  to: string
}

interface NavSection {
  heading?: string
  items: NavItem[]
}

const navSections = computed<NavSection[]>(() => [
  {
    items: [
      { icon: '▦', label: 'Dashboard', to: '/' },
    ],
  },
  {
    heading: 'Catalog',
    items: [
      { icon: '🔌', label: 'Providers', to: '/catalog/providers' },
      { icon: '📦', label: 'Projects', to: '/catalog/projects' },
      { icon: '🔧', label: 'Tech Stacks', to: '/catalog/tech-stacks' },
      { icon: '🚀', label: 'Pipelines', to: '/catalog/pipelines' },
    ],
  },
  {
    heading: 'Dependency',
    items: [
      { icon: '📋', label: 'Dependencies', to: '/dependency/dependencies' },
      { icon: '🛡', label: 'Vulnerabilities', to: '/dependency/vulnerabilities' },
    ],
  },
  {
    heading: 'Activity',
    items: [
      { icon: '⚡', label: 'Activity Events', to: '/activity/events' },
      { icon: '🔔', label: 'Notifications', to: '/activity/notifications' },
    ],
  },
  {
    heading: 'Assessment',
    items: [
      { icon: '📝', label: 'Quizzes', to: '/assessment/quizzes' },
      { icon: '❓', label: 'Questions', to: '/assessment/questions' },
      { icon: '🎯', label: 'Attempts', to: '/assessment/attempts' },
    ],
  },
  {
    heading: 'Identity',
    items: [
      { icon: '👤', label: 'Users', to: '/identity/users' },
      { icon: '🔑', label: 'Access Tokens', to: '/identity/access-tokens' },
      { icon: '👥', label: 'Teams', to: '/identity/teams' },
    ],
  },
])

const sidebarClasses = computed(() => [
  'fixed top-0 left-0 z-40 flex h-full flex-col bg-sidebar text-white transition-all duration-300',
  collapsed.value ? 'w-16' : 'w-64',
  mobileOpen.value ? 'max-md:translate-x-0' : 'max-md:-translate-x-full',
])

function isActive(path: string): boolean {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <aside
    :class="sidebarClasses"
    aria-label="Main navigation"
    data-testid="sidebar"
  >
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
      <span
        v-if="!collapsed"
        class="text-lg font-bold tracking-wide"
      >Monark</span>
      <button
        class="rounded p-1.5 hover:bg-sidebar-hover"
        aria-label="Toggle sidebar"
        data-testid="sidebar-toggle"
        @click="toggle"
      >
        <span class="text-lg">{{ collapsed ? '→' : '←' }}</span>
      </button>
    </div>

    <nav
      class="mt-4 flex-1 space-y-4 overflow-y-auto px-2"
      aria-label="Sidebar navigation"
    >
      <div
        v-for="(section, sIdx) in navSections"
        :key="sIdx"
      >
        <p
          v-if="section.heading && !collapsed"
          class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-white/40"
        >
          {{ section.heading }}
        </p>
        <div class="space-y-1">
          <RouterLink
            v-for="item in section.items"
            :key="item.to"
            :to="item.to"
            :class="[
              'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
              isActive(item.to)
                ? 'bg-sidebar-active text-white'
                : 'text-white/70 hover:bg-sidebar-hover hover:text-white',
            ]"
            :data-testid="`nav-${item.label.toLowerCase().replace(/\s+/g, '-')}`"
          >
            <span class="text-base">{{ item.icon }}</span>
            <span v-if="!collapsed">{{ item.label }}</span>
          </RouterLink>
        </div>
      </div>
    </nav>
  </aside>
</template>
