import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { CreateProjectInput, Project, ScanResult, UpdateProjectInput } from '@/catalog/types/project'
import { projectService } from '@/catalog/services/project.service'

export const useProjectStore = defineStore('catalog-project', () => {
  const projects = ref<Project[]>([])
  const selected = ref<Project | null>(null)
  const loading = ref(false)
  const scanning = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)
  const scanResult = ref<ScanResult | null>(null)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await projectService.list(page, perPage)
      projects.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load projects'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await projectService.get(id)
      selected.value = response.data
    } catch {
      error.value = 'Failed to load project'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateProjectInput): Promise<Project> {
    loading.value = true
    error.value = null

    try {
      const response = await projectService.create(data)
      projects.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create project'
      throw new Error('Failed to create project')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateProjectInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await projectService.update(id, data)
      selected.value = response.data
      const index = projects.value.findIndex(p => p.id === id)
      if (index !== -1) {
        projects.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update project'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await projectService.remove(id)
      projects.value = projects.value.filter(p => p.id !== id)
    } catch {
      error.value = 'Failed to delete project'
    } finally {
      loading.value = false
    }
  }

  async function scan(id: string): Promise<ScanResult> {
    scanning.value = true
    error.value = null
    scanResult.value = null

    try {
      const response = await projectService.scan(id)
      scanResult.value = response.data
      return response.data
    } catch {
      error.value = 'Failed to scan project'
      throw new Error('Failed to scan project')
    } finally {
      scanning.value = false
    }
  }

  return {
    projects,
    selected,
    loading,
    scanning,
    error,
    totalPages,
    currentPage,
    total,
    scanResult,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    scan,
  }
})
