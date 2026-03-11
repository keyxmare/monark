import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { Attempt, CreateAttemptInput } from '@/assessment/types/attempt'
import { attemptService } from '@/assessment/services/attempt.service'
import { i18n } from '@/shared/i18n'

export const useAttemptStore = defineStore('assessment-attempt', () => {
  const t = i18n.global.t
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
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.attempts') })
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
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.attempts') })
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
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.attempts') })
      throw new Error(error.value)
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
