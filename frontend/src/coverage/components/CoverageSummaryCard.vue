<script setup lang="ts">
import type { CoverageSummary } from '@/coverage/types';

defineProps<{ summary: CoverageSummary }>();
</script>

<template>
  <div class="grid grid-cols-2 gap-4 lg:grid-cols-5" data-testid="coverage-summary">
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Moyenne</p>
      <p class="text-2xl font-bold">
        {{ summary.averageCoverage !== null ? `${summary.averageCoverage}%` : '—' }}
      </p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Projets couverts</p>
      <p class="text-2xl font-bold">{{ summary.coveredProjects }} / {{ summary.totalProjects }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">&ge; 80%</p>
      <p class="text-2xl font-bold text-green-500">{{ summary.aboveThreshold }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">&lt; 80%</p>
      <p class="text-2xl font-bold text-orange-500">{{ summary.belowThreshold }}</p>
    </div>
    <div class="rounded-lg bg-surface p-4">
      <p class="text-sm text-text-muted">Tendance</p>
      <p class="text-2xl font-bold">
        <span v-if="summary.trend !== null && summary.trend > 0" class="text-green-500">&uarr; +{{ summary.trend }}</span>
        <span v-else-if="summary.trend !== null && summary.trend < 0" class="text-red-500">&darr; {{ summary.trend }}</span>
        <span v-else>—</span>
      </p>
    </div>
  </div>
</template>
