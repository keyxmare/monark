import { ref } from 'vue'
import { defineStore } from 'pinia'
import type {
  CreateProviderInput,
  ImportProjectsInput,
  Provider,
  RemoteProject,
  UpdateProviderInput,
} from '@/catalog/types/provider'
import type { Project } from '@/catalog/types/project'
import { providerService } from '@/catalog/services/provider.service'

export const useProviderStore = defineStore('catalog-provider', () => {
  const providers = ref<Provider[]>([])
  const selected = ref<Provider | null>(null)
  const remoteProjects = ref<RemoteProject[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const totalPages = ref(0)
  const currentPage = ref(1)
  const total = ref(0)
  const remoteProjectsTotalPages = ref(0)
  const remoteProjectsCurrentPage = ref(1)
  const remoteProjectsTotal = ref(0)

  async function fetchAll(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.list(page, perPage)
      providers.value = response.data.items
      totalPages.value = response.data.total_pages
      currentPage.value = response.data.page
      total.value = response.data.total
    } catch {
      error.value = 'Failed to load providers'
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.get(id)
      selected.value = response.data
    } catch {
      error.value = 'Failed to load provider'
    } finally {
      loading.value = false
    }
  }

  async function create(data: CreateProviderInput): Promise<Provider> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.create(data)
      providers.value.unshift(response.data)
      return response.data
    } catch {
      error.value = 'Failed to create provider'
      throw new Error('Failed to create provider')
    } finally {
      loading.value = false
    }
  }

  async function update(id: string, data: UpdateProviderInput): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.update(id, data)
      selected.value = response.data
      const index = providers.value.findIndex(p => p.id === id)
      if (index !== -1) {
        providers.value[index] = response.data
      }
    } catch {
      error.value = 'Failed to update provider'
    } finally {
      loading.value = false
    }
  }

  async function remove(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await providerService.remove(id)
      providers.value = providers.value.filter(p => p.id !== id)
    } catch {
      error.value = 'Failed to delete provider'
    } finally {
      loading.value = false
    }
  }

  async function testConnection(id: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.testConnection(id)
      if (selected.value && selected.value.id === id) {
        selected.value.status = response.data.connected ? 'connected' : 'error'
      }
      const index = providers.value.findIndex(p => p.id === id)
      if (index !== -1) {
        providers.value[index].status = response.data.connected ? 'connected' : 'error'
      }
      return response.data.connected
    } catch {
      error.value = 'Failed to test connection'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchRemoteProjects(id: string, page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.listRemoteProjects(id, page, perPage)
      const data = response.data
      if (Array.isArray(data)) {
        remoteProjects.value = data
        remoteProjectsTotal.value = data.length
        remoteProjectsTotalPages.value = 1
        remoteProjectsCurrentPage.value = 1
      } else {
        remoteProjects.value = data.items
        remoteProjectsTotalPages.value = data.total_pages
        remoteProjectsCurrentPage.value = data.page
        remoteProjectsTotal.value = data.total
      }
    } catch {
      error.value = 'Failed to load remote projects'
    } finally {
      loading.value = false
    }
  }

  async function importProjects(id: string, data: ImportProjectsInput): Promise<Project[]> {
    loading.value = true
    error.value = null

    try {
      const response = await providerService.importProjects(id, data)
      remoteProjects.value = remoteProjects.value.map(rp =>
        data.projects.some(p => p.externalId === rp.externalId)
          ? { ...rp, alreadyImported: true }
          : rp,
      )
      return response.data
    } catch {
      error.value = 'Failed to import projects'
      throw new Error('Failed to import projects')
    } finally {
      loading.value = false
    }
  }

  return {
    providers,
    selected,
    remoteProjects,
    loading,
    error,
    totalPages,
    currentPage,
    total,
    remoteProjectsTotalPages,
    remoteProjectsCurrentPage,
    remoteProjectsTotal,
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    testConnection,
    fetchRemoteProjects,
    importProjects,
  }
})
