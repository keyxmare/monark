import { ref } from 'vue';
import { defineStore } from 'pinia';
import { dashboardService } from '@/apps/monitoring/activity/services/dashboard.service';
import type { DashboardSummary } from '@/apps/monitoring/activity/services/dashboard.service';
import { i18n } from '@/hub/shared/i18n';

export type { DashboardSummary };

export const useDashboardStore = defineStore('dashboard', () => {
  const t = i18n.global.t;
  const summary = ref<DashboardSummary | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      summary.value = await dashboardService.getDashboard();
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.dashboard') });
    } finally {
      loading.value = false;
    }
  }

  return {
    error,
    load,
    loading,
    summary,
  };
});
