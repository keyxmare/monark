<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { useSidebar } from '@/shared/composables/useSidebar'

const route = useRoute()
const { collapsed, mobileOpen, toggle } = useSidebar()

const navItems = computed(() => [
  { icon: '▦', label: 'Dashboard', to: '/' },
])

const sidebarClasses = computed(() => [
  'fixed top-0 left-0 z-40 flex h-full flex-col bg-sidebar text-white transition-all duration-300',
  collapsed.value ? 'w-16' : 'w-64',
  mobileOpen.value ? 'max-md:translate-x-0' : 'max-md:-translate-x-full',
])

function isActive(path: string): boolean {
  return route.path === path
}
</script>

<template>
  <aside :class="sidebarClasses" aria-label="Main navigation" data-testid="sidebar">
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
      <span v-if="!collapsed" class="text-lg font-bold tracking-wide">Monark</span>
      <button
        class="rounded p-1.5 hover:bg-sidebar-hover"
        aria-label="Toggle sidebar"
        data-testid="sidebar-toggle"
        @click="toggle"
      >
        <span class="text-lg">{{ collapsed ? '→' : '←' }}</span>
      </button>
    </div>

    <nav class="mt-4 flex-1 space-y-1 px-2" aria-label="Sidebar navigation">
      <RouterLink
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        :class="[
          'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
          isActive(item.to)
            ? 'bg-sidebar-active text-white'
            : 'text-white/70 hover:bg-sidebar-hover hover:text-white',
        ]"
        :data-testid="`nav-${item.label.toLowerCase()}`"
      >
        <span class="text-base">{{ item.icon }}</span>
        <span v-if="!collapsed">{{ item.label }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>
