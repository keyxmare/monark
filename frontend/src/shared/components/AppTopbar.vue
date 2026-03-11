<script setup lang="ts">
import { useSidebar } from '@/shared/composables/useSidebar'

const { toggleMobile } = useSidebar()
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
      class="flex items-center gap-3"
      data-testid="topbar-user-area"
    >
      <div
        class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-medium text-white"
        data-testid="user-avatar"
      >
        U
      </div>
    </div>
  </header>
</template>
