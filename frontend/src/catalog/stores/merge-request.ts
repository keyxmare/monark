import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { MergeRequest } from '@/catalog/types/merge-request'
import { mergeRequestService } from '@/catalog/services/merge-request.service'
import { i18n } from '@/shared/i18n'

export const useMergeRequestStore = defineStore('catalog-merge-request', () => {
  const t = i18n.global.t
  const mergeRequests = ref<MergeRequest[]>([])
  const selected = ref<MergeRequest | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(projectId: string, page = 1, perPage = 20, status?: string, author?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await mergeRequestService.list(projectId, page, perPage, status, author)
      mergeRequests.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.mergeRequests') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await mergeRequestService.get(id)
      selected.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.mergeRequests') })
    } finally {
      loading.value = false
    }
  }

  return {
    mergeRequests,
    selected,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
  }
})
