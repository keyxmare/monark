<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import { humanizeMs, msUrgency } from '@/catalog/composables/useFrameworkLts';

const { t } = useI18n();

defineProps<{
  depGapStats: null | {
    average: number;
    cumulated: number;
    median: number;
  };
  healthScore: null | {
    outdated: number;
    percent: number;
    total: number;
    totalVulns: number;
    upToDate: number;
  };
}>();
</script>

<template>
  <div
    v-if="healthScore"
    class="mb-6 flex flex-wrap items-center gap-4 rounded-xl border border-border bg-surface p-4"
    data-testid="dependency-health-score"
  >
    <div class="flex-1">
      <div class="mb-1 flex items-center justify-between text-sm">
        <span class="font-medium text-text">{{
          t('dependency.dependencies.healthScore', { percent: healthScore.percent })
        }}</span>
        <span class="text-text-muted">{{ healthScore.upToDate }}/{{ healthScore.total }}</span>
      </div>
      <div class="h-2 w-full overflow-hidden rounded-full bg-surface-muted">
        <div
          class="h-full rounded-full bg-success transition-all"
          :style="{ width: `${healthScore.percent}%` }"
        />
      </div>
    </div>
    <div
      v-if="healthScore.outdated > 0"
      class="rounded-full bg-danger/10 px-3 py-1 text-sm font-medium text-danger"
    >
      {{ t('dependency.dependencies.healthOutdated', { count: healthScore.outdated }) }}
    </div>
    <div
      v-if="healthScore.totalVulns > 0"
      class="rounded-full bg-warning/10 px-3 py-1 text-sm font-medium text-warning"
    >
      {{ t('dependency.dependencies.healthVulns', { count: healthScore.totalVulns }) }}
    </div>
  </div>

  <!-- Gap stats -->
  <div v-if="depGapStats" class="mb-6 grid grid-cols-3 gap-4" data-testid="dep-gap-stats">
    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <p class="text-xs text-text-muted">
        {{ t('catalog.techStacks.gapCumulated') }}
      </p>
      <p
        :class="{
          'text-success': msUrgency(depGapStats.cumulated) === 'fresh',
          'text-warning': msUrgency(depGapStats.cumulated) === 'moderate',
          'text-danger': msUrgency(depGapStats.cumulated) === 'outdated',
        }"
        class="mt-1 text-lg font-bold"
      >
        {{ humanizeMs(depGapStats.cumulated) }}
      </p>
    </div>
    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <p class="text-xs text-text-muted">
        {{ t('catalog.techStacks.gapAverage') }}
      </p>
      <p
        :class="{
          'text-success': msUrgency(depGapStats.average) === 'fresh',
          'text-warning': msUrgency(depGapStats.average) === 'moderate',
          'text-danger': msUrgency(depGapStats.average) === 'outdated',
        }"
        class="mt-1 text-lg font-bold"
      >
        {{ humanizeMs(depGapStats.average) }}
      </p>
    </div>
    <div class="rounded-xl border border-border bg-surface p-4 text-center">
      <p class="text-xs text-text-muted">
        {{ t('catalog.techStacks.gapMedian') }}
      </p>
      <p
        :class="{
          'text-success': msUrgency(depGapStats.median) === 'fresh',
          'text-warning': msUrgency(depGapStats.median) === 'moderate',
          'text-danger': msUrgency(depGapStats.median) === 'outdated',
        }"
        class="mt-1 text-lg font-bold"
      >
        {{ humanizeMs(depGapStats.median) }}
      </p>
    </div>
  </div>
</template>
