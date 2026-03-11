<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/identity/stores/auth'
import { useSidebar } from '@/shared/composables/useSidebar'

const { toggleMobile } = useSidebar()
const router = useRouter()
const authStore = useAuthStore()
const menuOpen = ref(false)

const userInitials = computed(() => {
  const user = authStore.currentUser
  if (!user) return '?'
  return `${user.firstName?.[0] ?? ''}${user.lastName?.[0] ?? ''}`.toUpperCase() || user.email[0].toUpperCase()
})

const userName = computed(() => {
  const user = authStore.currentUser
  if (!user) return ''
  return `${user.firstName} ${user.lastName}`.trim() || user.email
})

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <header
    class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-surface px-6"
    data-testid="topbar"
  >
    <div class="flex items-center gap-4">
      <button
        class="rounded p-2 hover:bg-surface-muted md:hidden"
        aria-label="Open menu"
        data-testid="topbar-menu-toggle"
        @click="toggleMobile"
      >
        <span class="text-xl">☰</span>
      </button>

      <h1 class="text-lg font-semibold text-text">
        Monark
      </h1>
    </div>

    <div
      class="relative flex items-center gap-3"
      data-testid="topbar-user-area"
    >
      <button
        class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-muted"
        data-testid="user-menu-toggle"
        @click="menuOpen = !menuOpen"
      >
        <div
          class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-medium text-white"
          data-testid="user-avatar"
        >
          {{ userInitials }}
        </div>
        <span class="hidden text-sm font-medium text-text sm:block">{{ userName }}</span>
      </button>

      <div
        v-if="menuOpen"
        class="absolute right-0 top-full mt-1 w-48 rounded-lg border border-border bg-surface py-1 shadow-lg"
        data-testid="user-menu"
      >
        <button
          class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-text transition-colors hover:bg-surface-muted"
          data-testid="logout-btn"
          @click="handleLogout"
        >
          Logout
        </button>
      </div>
    </div>
  </header>
</template>
