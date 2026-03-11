import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateTechStackInput, TechStack } from '@/catalog/types/tech-stack'
import { techStackService } from '@/catalog/services/tech-stack.service'
import { i18n } from '@/shared/i18n'

export const useTechStackStore = defineStore('catalog-tech-stack', () => {
  const t = i18n.global.t
  const techStacks = ref<TechStack[]>([])
  const selected = ref<TechStack | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await techStackService.list(page, perPage, projectId)
      techStacks.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.techStacks') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await techStackService.get(id)
      selected.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.techStacks') })
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateTechStackInput): Promise<TechStack> {
    loading.value = true
    error.value = null

    try {
      const response = await techStackService.create(data)
      techStacks.value.unshift(response.data)
      return response.data
    } catch {
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.techStacks') })
      throw new Error(error.value)
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await techStackService.remove(id)
      techStacks.value = techStacks.value.filter(ts => ts.id !== id)
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.techStacks') })
    } finally {
      loading.value = false
    }
  }

  return {
    techStacks,
    selected,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
    remove,
  }
})
