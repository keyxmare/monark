import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { CoverageDashboard } from '@/coverage/types';
import { coverageService } from '@/coverage/services/coverage.service';

export const useCoverageStore = defineStore('coverage', () => {
  const dashboard = ref<CoverageDashboard | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function fetchDashboard(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      dashboard.value = await coverageService.getDashboard();
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to fetch coverage data';
    } finally {
      loading.value = false;
    }
  }

  return { dashboard, loading, error, fetchDashboard };
});
