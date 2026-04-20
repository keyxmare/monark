<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

interface AppEntry {
  key: string;
  labelKey: string;
  path: string;
}

const APPS: AppEntry[] = [
  { key: 'monitoring', labelKey: 'hub.apps.monitoring', path: '/monitoring/dashboard' },
];

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const open = ref(false);

const currentApp = computed(() => {
  if (route.path.startsWith('/monitoring')) return 'monitoring';
  return undefined;
});

const currentLabel = computed(() => {
  const app = APPS.find((a) => a.key === currentApp.value);
  return app ? t(app.labelKey) : t('hub.app_switcher.no_app');
});

function handleOutsideClick(event: MouseEvent) {
  const target = event.target as HTMLElement;
  if (!target.closest('[data-testid="app-switcher"]')) {
    open.value = false;
  }
}

function selectApp(app: AppEntry) {
  open.value = false;
  router.push(app.path);
}

document.addEventListener('click', handleOutsideClick);
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick));
</script>

<template>
  <div class="relative" data-testid="app-switcher">
    <button
      type="button"
      class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium text-text hover:bg-surface-muted"
      :aria-expanded="open"
      :aria-label="t('hub.app_switcher.label')"
      data-testid="app-switcher-toggle"
      @click="open = !open"
    >
      <span>{{ currentLabel }}</span>
      <svg class="h-3 w-3" viewBox="0 0 12 12" aria-hidden="true">
        <path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" />
      </svg>
    </button>

    <div
      v-if="open"
      class="absolute left-0 top-full z-30 mt-1 w-48 rounded-md border border-border bg-surface shadow-lg"
      role="menu"
      data-testid="app-switcher-menu"
    >
      <button
        v-for="app in APPS"
        :key="app.key"
        type="button"
        class="block w-full px-3 py-2 text-left text-sm text-text hover:bg-surface-muted"
        :class="{ 'font-semibold': app.key === currentApp }"
        role="menuitem"
        @click="selectApp(app)"
      >
        {{ t(app.labelKey) }}
      </button>
    </div>
  </div>
</template>
