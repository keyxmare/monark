<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import type { ProviderStatus } from '@/catalog/types/provider';

defineProps<{
  apiLatency: null | number;
  projectsCount: number;
  status: ProviderStatus;
  syncFreshness: 'fresh' | 'recent' | 'stale';
}>();

const { t } = useI18n();
</script>

<template>
  <div
    class="mb-6 grid max-w-3xl grid-cols-2 gap-4 sm:grid-cols-4"
    data-testid="provider-health-stats"
  >
    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <div
        :class="{
          'text-green-600': status === 'connected',
          'text-yellow-600': status === 'pending',
          'text-red-600': status === 'error',
        }"
        class="text-lg font-bold"
      >
        {{ t(`catalog.providers.statuses.${status}`) }}
      </div>
      <p class="text-xs text-text-muted">
        {{ t('catalog.providers.health.status') }}
      </p>
    </div>

    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <div class="text-lg font-bold tabular-nums text-text">
        {{ projectsCount }}
      </div>
      <p class="text-xs text-text-muted">
        {{ t('catalog.providers.projects') }}
      </p>
    </div>

    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <div
        :class="{
          'text-green-600': syncFreshness === 'fresh',
          'text-yellow-600': syncFreshness === 'recent',
          'text-red-600': syncFreshness === 'stale',
        }"
        class="text-lg font-bold"
      >
        {{ t(`catalog.providers.health.${syncFreshness}`) }}
      </div>
      <p class="text-xs text-text-muted">
        {{ t('catalog.providers.health.syncAge') }}
      </p>
    </div>

    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <div class="text-lg font-bold tabular-nums text-text">
        {{ apiLatency !== null ? `${apiLatency}ms` : '---' }}
      </div>
      <p class="text-xs text-text-muted">
        {{ t('catalog.providers.health.latency') }}
      </p>
    </div>
  </div>
</template>
