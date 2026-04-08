<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import AppSidebar from '@/shared/components/AppSidebar.vue';
import AppTopbar from '@/shared/components/AppTopbar.vue';
import SyncProgressBanner from '@/shared/components/SyncProgressBanner.vue';
import { provideGlobalSync } from '@/shared/composables/useGlobalSync';
import { useSidebar } from '@/shared/composables/useSidebar';

const { t } = useI18n();
const { collapsed, mobileOpen } = useSidebar();
const { loadCurrent } = provideGlobalSync();

onMounted(loadCurrent);

const mainClasses = computed(() => [
  'min-h-screen transition-all duration-300',
  collapsed.value ? 'ml-16' : 'ml-64',
  'max-md:ml-0',
]);
</script>

<template>
  <div class="flex min-h-screen bg-surface-muted">
    <a
      href="#main-content"
      class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded focus:bg-primary focus:px-4 focus:py-2 focus:text-white"
    >
      {{ t('aria.skipToContent') }}
    </a>
    <AppSidebar />

    <div
      v-if="mobileOpen"
      role="button"
      tabindex="0"
      :aria-label="t('aria.closeSidebar')"
      class="fixed inset-0 z-30 bg-black/50 md:hidden"
      data-testid="sidebar-overlay"
      @click="mobileOpen = false"
      @keydown.escape="mobileOpen = false"
    />

    <div :class="mainClasses" class="flex-1">
      <AppTopbar />
      <SyncProgressBanner />

      <main id="main-content" class="w-full p-6" data-testid="main-content">
        <slot />
      </main>
    </div>
  </div>
</template>
