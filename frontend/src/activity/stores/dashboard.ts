import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface DashboardMetric {
  change?: number
  label: string
  value: number | string
}

export const useDashboardStore = defineStore('dashboard', () => {
  const metrics = ref<DashboardMetric[]>([])
  const loading = ref(false)

  async function load() {
    loading.value = true
    try {
      const response = await fetch('/api/dashboard/metrics')
      if (response.ok) {
        const data = await response.json()
        metrics.value = data.data ?? []
      }
    } finally {
      loading.value = false
    }
  }

  return {
    load,
    loading,
    metrics,
  }
})
