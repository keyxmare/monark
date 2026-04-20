<script setup lang="ts">
import { computed } from 'vue';

import type { CoverageJob } from '@/apps/monitoring/catalog/types/project';

type Tone = 'bad' | 'good' | 'warn';

const props = defineProps<{
  jobs: CoverageJob[];
}>();

function tone(percent: number): Tone {
  if (percent >= 80) return 'good';
  if (percent >= 50) return 'warn';
  return 'bad';
}

const sortedJobs = computed<CoverageJob[]>(() => {
  return [...props.jobs].sort((a, b) => b.percent - a.percent);
});
</script>

<template>
  <div v-if="sortedJobs.length > 0" class="coverage-jobs">
    <div v-for="job in sortedJobs" :key="job.name" class="coverage-job">
      <span class="coverage-job-name" :title="job.name">{{ job.name }}</span>
      <div class="coverage-job-track" aria-hidden="true">
        <span
          class="coverage-job-fill"
          :class="`tone-${tone(job.percent)}`"
          :style="{ width: `${Math.max(2, Math.min(100, job.percent))}%` }"
        />
      </div>
      <span class="coverage-job-percent" :class="`tone-${tone(job.percent)}`">
        {{ Math.round(job.percent) }}%
      </span>
    </div>
  </div>
</template>

<style scoped>
.coverage-jobs {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.coverage-job {
  display: grid;
  grid-template-columns: minmax(120px, 1.5fr) 1fr 48px;
  align-items: center;
  gap: 12px;
}
.coverage-job-name {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  color: var(--hub-fg-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.coverage-job-track {
  height: 6px;
  border-radius: 3px;
  background: color-mix(in oklab, var(--hub-line-strong) 80%, transparent);
  overflow: hidden;
}
.coverage-job-fill {
  display: block;
  height: 100%;
  border-radius: 3px;
  transition: width 900ms var(--hub-ease);
}
.coverage-job-fill.tone-good {
  background: #4ade80;
}
.coverage-job-fill.tone-warn {
  background: #ffb547;
}
.coverage-job-fill.tone-bad {
  background: #ff5c5c;
}
.coverage-job-percent {
  font-family: var(--hub-font-mono);
  font-size: 11px;
  text-align: right;
}
.coverage-job-percent.tone-good {
  color: color-mix(in oklab, #4ade80 85%, white);
}
.coverage-job-percent.tone-warn {
  color: #ffc87a;
}
.coverage-job-percent.tone-bad {
  color: #ff8080;
}
</style>
