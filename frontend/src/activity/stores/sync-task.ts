import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { SyncTask, SyncTaskStats } from '@/activity/types/sync-task'
import { syncTaskService, type SyncTaskFilters } from '@/activity/services/sync-task.service'
import { i18n } from '@/shared/i18n'

export const useSyncTaskStore = defineStore('activity-sync-task', () => {
  const t = i18n.global.t
  const tasks = ref<SyncTask[]>([])
  const stats = ref<SyncTaskStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(filters: SyncTaskFilters = {}): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await syncTaskService.list(filters)
      tasks.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.syncTasks') })
    } finally {
      loading.value = false
    }
  }

  async function fetchStats(): Promise<void> {
    try {
      const response = await syncTaskService.getStats()
      stats.value = response.data
    } catch {
      // stats are non-critical, silently fail
    }
  }

  async function updateStatus(id: string, status: string): Promise<void> {
    error.value = null

    try {
      const response = await syncTaskService.updateStatus(id, status)
      const index = tasks.value.findIndex(t => t.id === id)
      if (index !== -1) {
        tasks.value[index] = response.data
      }
    } catch {
      error.value = t('common.errors.failedToUpdate', { entity: t('common.entities.syncTasks') })
    }
  }

  return {
    tasks,
    stats,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchStats,
    updateStatus,
  }
})
