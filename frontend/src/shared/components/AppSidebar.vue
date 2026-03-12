<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'

import { useSidebar } from '@/shared/composables/useSidebar'

const route = useRoute()
const { t } = useI18n()
const { collapsed, mobileOpen, toggle } = useSidebar()

interface NavItem {
  icon: string
  labelKey: string
  to: string
}

interface NavSection {
  headingKey?: string
  items: NavItem[]
}

const navSections = computed<NavSection[]>(() => [
  {
    items: [
      { icon: '▦', labelKey: 'nav.dashboard', to: '/' },
    ],
  },
  {
    headingKey: 'nav.sections.catalog',
    items: [
      { icon: '🔌', labelKey: 'nav.providers', to: '/catalog/providers' },
      { icon: '📦', labelKey: 'nav.projects', to: '/catalog/projects' },
      { icon: '🔧', labelKey: 'nav.techStacks', to: '/catalog/tech-stacks' },
      { icon: '🚀', labelKey: 'nav.pipelines', to: '/catalog/pipelines' },
    ],
  },
  {
    headingKey: 'nav.sections.dependency',
    items: [
      { icon: '📋', labelKey: 'nav.dependencies', to: '/dependency/dependencies' },
      { icon: '🛡', labelKey: 'nav.vulnerabilities', to: '/dependency/vulnerabilities' },
    ],
  },
  {
    headingKey: 'nav.sections.activity',
    items: [
      { icon: '⚡', labelKey: 'nav.activityEvents', to: '/activity/events' },
      { icon: '🔔', labelKey: 'nav.notifications', to: '/activity/notifications' },
      { icon: '🔄', labelKey: 'nav.syncTasks', to: '/activity/sync-tasks' },
      { icon: '📨', labelKey: 'nav.messenger', to: '/activity/messenger' },
    ],
  },
  {
    headingKey: 'nav.sections.assessment',
    items: [
      { icon: '📝', labelKey: 'nav.quizzes', to: '/assessment/quizzes' },
      { icon: '❓', labelKey: 'nav.questions', to: '/assessment/questions' },
      { icon: '🎯', labelKey: 'nav.attempts', to: '/assessment/attempts' },
    ],
  },
  {
    headingKey: 'nav.sections.identity',
    items: [
      { icon: '👤', labelKey: 'nav.users', to: '/identity/users' },
      { icon: '🔑', labelKey: 'nav.accessTokens', to: '/identity/access-tokens' },
      { icon: '👥', labelKey: 'nav.teams', to: '/identity/teams' },
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
    :aria-label="t('aria.mainNavigation')"
    data-testid="sidebar"
  >
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
      <span
        v-if="!collapsed"
        class="text-lg font-bold tracking-wide"
      >Monark</span>
      <button
        class="rounded p-1.5 hover:bg-sidebar-hover"
        :aria-label="t('aria.toggleSidebar')"
        data-testid="sidebar-toggle"
        @click="toggle"
      >
        <span class="text-lg">{{ collapsed ? '→' : '←' }}</span>
      </button>
    </div>

    <nav
      class="mt-4 flex-1 space-y-4 overflow-y-auto px-2"
      :aria-label="t('aria.sidebarNavigation')"
    >
      <div
        v-for="(section, sIdx) in navSections"
        :key="sIdx"
      >
        <p
          v-if="section.headingKey && !collapsed"
          class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-white/40"
        >
          {{ t(section.headingKey) }}
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
            :data-testid="`nav-${t(item.labelKey).toLowerCase().replace(/\s+/g, '-')}`"
          >
            <span class="text-base">{{ item.icon }}</span>
            <span v-if="!collapsed">{{ t(item.labelKey) }}</span>
          </RouterLink>
        </div>
      </div>
    </nav>
  </aside>
</template>
