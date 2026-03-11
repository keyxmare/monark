import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateDependencyInput, Dependency, UpdateDependencyInput } from '@/dependency/types/dependency'
import { dependencyService } from '@/dependency/services/dependency.service'
import { i18n } from '@/shared/i18n'

export const useDependencyStore = defineStore('dependency', () => {
  const t = i18n.global.t
  const dependencies = ref<Dependency[]>([])
  const selectedDependency = ref<Dependency | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)

  async function fetchAll(page = 1, perPage = 20, projectId?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await dependencyService.list(page, perPage, projectId)
      dependencies.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.dependencies') })
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await dependencyService.get(id)
      selectedDependency.value = response.data
    } catch {
      error.value = t('common.errors.failedToLoad', { entity: t('common.entities.dependencies') })
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateDependencyInput): Promise<Dependency> {
    loading.value = true
    error.value = null

    try {
      const response = await dependencyService.create(data)
      dependencies.value.unshift(response.data)
      return response.data
    } catch {
      error.value = t('common.errors.failedToCreate', { entity: t('common.entities.dependencies') })
      throw new Error(error.value)
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateDependencyInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await dependencyService.update(id, data)
      selectedDependency.value = response.data
      const index = dependencies.value.findIndex(d => d.id === id)
      if (index !== -1) {
        dependencies.value[index] = response.data
      }
    } catch {
      error.value = t('common.errors.failedToUpdate', { entity: t('common.entities.dependencies') })
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await dependencyService.remove(id)
      dependencies.value = dependencies.value.filter(d => d.id !== id)
    } catch {
      error.value = t('common.errors.failedToDelete', { entity: t('common.entities.dependencies') })
    } finally {
      loading.value = false
    }
  }

  return {
    dependencies,
    selectedDependency,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
  }
})
