import { ref } from 'vue';
import { defineStore } from 'pinia';
import type { ActivityEvent } from '@/activity/types/activity-event';
import { activityEventService } from '@/activity/services/activity-event.service';
import { i18n } from '@/shared/i18n';

export const useActivityEventStore = defineStore('activityEvent', () => {
  const t = i18n.global.t;
  const events = ref<ActivityEvent[]>([]);
  const selectedEvent = ref<ActivityEvent | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const totalPages = ref(0);
  const currentPage = ref(1);
  const total = ref(0);

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await activityEventService.list(page, perPage);
      events.value = response.data.items;
      totalPages.value = response.data.total_pages;
      currentPage.value = response.data.page;
      total.value = response.data.total;
    } catch {
      error.value = t('common.errors.failedToLoad', {
        entity: t('common.entities.activityEvents'),
      });
    } finally {
      loading.value = false;
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const response = await activityEventService.get(id);
      selectedEvent.value = response.data;
    } catch {
      error.value = t('common.errors.failedToLoad', {
        entity: t('common.entities.activityEvents'),
      });
    } finally {
      loading.value = false;
    }
  }

  return {
    currentPage,
    error,
    events,
    fetchAll,
    fetchOne,
    loading,
    selectedEvent,
    total,
    totalPages,
  };
});
