<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import type { Provider } from '@/catalog/types/provider';

import ProviderIcon from '@/catalog/components/ProviderIcon.vue';
import DropdownMenu from '@/shared/components/DropdownMenu.vue';

defineProps<{
  items: Array<{ action: string; disabled?: boolean; label: string; variant?: 'danger' }>;
  provider: Provider;
}>();

defineEmits<{
  dropdownAction: [action: string];
  navigate: [];
}>();

const { d, t } = useI18n();
</script>

<template>
  <div
    class="cursor-pointer rounded-xl border border-border bg-surface p-5 shadow-sm transition-shadow hover:shadow-md"
    data-testid="provider-list-card"
    role="link"
    tabindex="0"
    @click="$emit('navigate')"
    @keydown.enter="$emit('navigate')"
  >
    <div class="mb-3 flex items-start justify-between">
      <div class="flex items-center gap-3">
        <ProviderIcon
          :type="provider.type"
          :size="24"
        />
        <div>
          <h3 class="text-sm font-semibold text-text">
            {{ provider.name }}
          </h3>
          <p class="text-xs text-text-muted">
            {{ t(`catalog.providers.types.${provider.type}`) }}
          </p>
        </div>
      </div>
      <div
        class="flex items-center gap-2"
        @click.stop
      >
        <span
          :class="{
            'bg-green-100 text-green-800': provider.status === 'connected',
            'bg-yellow-100 text-yellow-800': provider.status === 'pending',
            'bg-red-100 text-red-800': provider.status === 'error',
          }"
          class="rounded-full px-2 py-0.5 text-xs font-medium"
          data-testid="provider-status-badge"
        >
          {{ t(`catalog.providers.statuses.${provider.status}`) }}
        </span>
        <DropdownMenu
          :items="items"
          @select="$emit('dropdownAction', $event)"
        />
      </div>
    </div>

    <div
      class="mb-3"
      @click.stop
    >
      <a
        :href="provider.url"
        class="truncate text-xs text-primary hover:underline"
        data-testid="provider-url-link"
        rel="noopener"
        target="_blank"
      >
        {{ provider.url }}
      </a>
    </div>

    <div class="flex items-center justify-between border-t border-border pt-3">
      <div class="flex items-center gap-4">
        <div data-testid="provider-projects-count">
          <p class="text-lg font-bold tabular-nums text-text">
            {{ provider.projectsCount }}
          </p>
          <p class="text-xs text-text-muted">
            {{ t('catalog.providers.projects') }}
          </p>
        </div>
      </div>
      <div class="text-right">
        <p class="text-xs text-text-muted">
          {{ t('catalog.providers.lastSync') }}
        </p>
        <p class="text-xs text-text-muted">
          {{ provider.lastSyncAt ? d(new Date(provider.lastSyncAt), 'short') : '---' }}
        </p>
      </div>
    </div>
  </div>
</template>
