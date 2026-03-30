<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

import { useDashboardStore } from '@/activity/stores/dashboard';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const { t } = useI18n();
const dashboardStore = useDashboardStore();

onMounted(async () => {
  await dashboardStore.load();
});

const metrics = computed(() => dashboardStore.metrics);
const loading = computed(() => dashboardStore.loading);
</script>

<template>
  <DashboardLayout>
    <div data-testid="dashboard-page">
      <h2 class="mb-6 text-2xl font-bold text-text" data-testid="dashboard-title">
        {{ t('activity.dashboard.welcome') }}
      </h2>

      <div
        v-if="loading"
        class="flex items-center justify-center py-12"
        data-testid="dashboard-loading"
      >
        <span class="text-text-muted">{{ t('common.actions.loading') }}</span>
      </div>

      <div
        v-else
        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4"
        data-testid="metrics-grid"
      >
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="rounded-xl border border-border bg-surface p-6 shadow-sm"
          data-testid="metric-card"
        >
          <p class="text-sm font-medium text-text-muted">
            {{ metric.label }}
          </p>
          <p class="mt-2 text-3xl font-bold text-text">
            {{ metric.value }}
          </p>
          <p
            v-if="metric.change !== undefined"
            class="mt-1 text-sm"
            :class="[metric.change >= 0 ? 'text-success' : 'text-danger']"
          >
            {{ metric.change >= 0 ? '+' : '' }}{{ metric.change }}%
          </p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
