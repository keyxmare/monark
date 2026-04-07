import { ref } from 'vue';
import { defineStore } from 'pinia';

import { historyService } from '@/history/services/history.service';
import type { BackfillRequest, DebtTimelinePoint } from '@/history/types/history';

export const useHistoryStore = defineStore('history', () => {
  const timeline = ref<DebtTimelinePoint[]>([]);
  const loading = ref(false);
  const backfillScheduled = ref(false);
  const error = ref<string | null>(null);

  async function loadTimeline(projectId: string, from?: string, to?: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await historyService.getTimeline(projectId, from, to);
      timeline.value = response.data;
    } catch {
      error.value = 'Failed to load debt timeline';
      timeline.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function triggerBackfill(projectId: string, payload: BackfillRequest): Promise<void> {
    backfillScheduled.value = false;
    error.value = null;
    try {
      const response = await historyService.triggerBackfill(projectId, payload);
      backfillScheduled.value = response.data.scheduled;
    } catch {
      error.value = 'Failed to schedule backfill';
    }
  }

  function reset(): void {
    timeline.value = [];
    backfillScheduled.value = false;
    error.value = null;
  }

  return {
    backfillScheduled,
    error,
    loadTimeline,
    loading,
    reset,
    timeline,
    triggerBackfill,
  };
});
