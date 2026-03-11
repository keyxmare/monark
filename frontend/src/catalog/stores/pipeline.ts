import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreatePipelineInput, Pipeline } from '@/catalog/types/pipeline'
import { pipelineService } from '@/catalog/services/pipeline.service'
import { i18n } from '@/shared/i18n'

export const usePipelineStore = defineStore('catalog-pipeline', () => {
  const t = i18n.global.t
  const pipelines = ref<Pipeline[]>([])
  const selected = ref<Pipeline | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20, projectId?: string, ref?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await pipelineService.list(page, perPage, projectId, ref)
      pipelines.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.pipelines') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await pipelineService.get(id)
      selected.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.pipelines') })
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreatePipelineInput): Promise<Pipeline> {
    loading.value = true
    error.value = null

    try {
      const response = await pipelineService.create(data)
      pipelines.value.unshift(response.data)
      return response.data
    } catch {
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.pipelines') })
      throw new Error(error.value)
    } finally {
      loading.value = false
    }
  }

  return {
    pipelines,
    selected,
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
