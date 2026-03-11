import { ref } from 'vue'
import { defineStore } from 'pinia'
import { dashboardService } from '@/activity/services/dashboard.service'
import type { DashboardMetric } from '@/activity/services/dashboard.service'

export type { DashboardMetric }

export const useDashboardStore = defineStore('dashboard', () => {
  const metrics = ref<DashboardMetric[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await dashboardService.getDashboard()
      metrics.value = response.data.metrics
    } catch {
      error.value = 'Failed to load dashboard'
    } finally {
      loading.value = false
    }
  }

  return {
    error,
    load,
    loading,
    metrics,
  }
})
