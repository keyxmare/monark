import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { Attempt, CreateAttemptInput } from '@/assessment/types/attempt'
import { attemptService } from '@/assessment/services/attempt.service'

export const useAttemptStore = defineStore('assessment-attempt', () => {
  const attempts = ref<Attempt[]>([])
  const selectedAttempt = ref<Attempt | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await attemptService.list(page, perPage)
      attempts.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load attempts'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await attemptService.get(id)
      selectedAttempt.value = response.data
    } catch {
      error.value = 'Failed to load attempt'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateAttemptInput): Promise<Attempt> {
    loading.value = true
    error.value = null

    try {
      const response = await attemptService.create(data)
      attempts.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create attempt'
      throw new Error('Failed to create attempt')
    } finally {
      loading.value = false
    }
  }

  return {
    attempts,
    selectedAttempt,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
  }
})
