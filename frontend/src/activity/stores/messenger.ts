import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { QueueStats, WorkerStats } from '@/activity/types/messenger';
import { messengerService } from '@/activity/services/messenger.service';
import { i18n } from '@/shared/i18n';

export const useMessengerStore = defineStore('activity-messenger', () => {
  const t = i18n.global.t;
  const queues = ref<QueueStats[]>([]);
  const workers = ref<WorkerStats[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchStats(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await messengerService.getStats();
      queues.value = response.data.queues;
      workers.value = response.data.workers;
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.messenger') });
    } finally {
      loading.value = false;
    }
  }

  return {
    queues,
    workers,
    loading,
    error,
    fetchStats,
  };
});
