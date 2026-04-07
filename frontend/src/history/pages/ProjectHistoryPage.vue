<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';

import { useHistoryStore } from '@/history/stores/history';
import DashboardLayout from '@/shared/layouts/DashboardLayout.vue';

const route = useRoute();
const store = useHistoryStore();

const projectId = computed(() => String(route.params.id));
const since = ref<string>('');
const until = ref<string>('');
const intervalDays = ref<number>(30);

const timeline = computed(() => store.timeline);
const loading = computed(() => store.loading);
const backfillScheduled = computed(() => store.backfillScheduled);
const error = computed(() => store.error);

onMounted(async () => {
  await store.loadTimeline(projectId.value);
});

async function submitBackfill(): Promise<void> {
  if (!since.value || !until.value) return;
  await store.triggerBackfill(projectId.value, {
    intervalDays: intervalDays.value,
    since: new Date(since.value).toISOString(),
    until: new Date(until.value).toISOString(),
  });
  await store.loadTimeline(projectId.value);
}
</script>

<template>
  <DashboardLayout>
    <div data-testid="project-history-page">
      <h2 class="mb-6 text-2xl font-bold text-text">Project debt history</h2>

      <section class="mb-6 rounded-xl border border-border bg-surface p-4">
        <h3 class="mb-4 text-lg font-semibold text-text">Backfill historical snapshots</h3>
        <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitBackfill">
          <label class="flex flex-col text-sm">
            <span class="text-text-muted">Since</span>
            <input v-model="since" type="date" class="rounded border border-border px-2 py-1" />
          </label>
          <label class="flex flex-col text-sm">
            <span class="text-text-muted">Until</span>
            <input v-model="until" type="date" class="rounded border border-border px-2 py-1" />
          </label>
          <label class="flex flex-col text-sm">
            <span class="text-text-muted">Interval (days)</span>
            <input
              v-model.number="intervalDays"
              type="number"
              min="1"
              class="rounded border border-border px-2 py-1"
            />
          </label>
          <button
            type="submit"
            class="rounded bg-primary px-4 py-2 text-sm font-medium text-white"
            data-testid="backfill-submit"
          >
            Backfill
          </button>
        </form>
        <p v-if="backfillScheduled" class="mt-2 text-sm text-success">Backfill scheduled.</p>
        <p v-if="error" class="mt-2 text-sm text-danger">{{ error }}</p>
      </section>

      <section data-testid="timeline-section">
        <div v-if="loading" class="text-text-muted">Loading…</div>
        <table v-else-if="timeline.length > 0" class="w-full text-sm">
          <thead>
            <tr class="border-b border-border text-left text-text-muted">
              <th class="py-2">Date</th>
              <th>Source</th>
              <th>Total</th>
              <th>Outdated</th>
              <th>Major</th>
              <th>Minor</th>
              <th>Patch</th>
              <th>LTS gap</th>
              <th>Score</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="point in timeline"
              :key="point.snapshotId"
              class="border-b border-border"
              data-testid="timeline-row"
            >
              <td class="py-2">{{ point.snapshotDate }}</td>
              <td>{{ point.source }}</td>
              <td>{{ point.totalDeps }}</td>
              <td>{{ point.outdatedCount }}</td>
              <td>{{ point.majorGapCount }}</td>
              <td>{{ point.minorGapCount }}</td>
              <td>{{ point.patchGapCount }}</td>
              <td>{{ point.ltsGapCount }}</td>
              <td>{{ point.debtScore }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="text-text-muted">No snapshots yet.</div>
      </section>
    </div>
  </DashboardLayout>
</template>
