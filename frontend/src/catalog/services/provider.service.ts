import type { ApiResponse } from '@/shared/types'
import { api } from '@/shared/utils/api'
import type {
  CreateProviderInput,
  ImportProjectsInput,
  Provider,
  RemoteProject,
  UpdateProviderInput,
} from '@/catalog/types/provider'
import type { Project } from '@/catalog/types/project'

interface PaginatedProviders {
  items: Provider[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

interface PaginatedRemoteProjects {
  items: RemoteProject[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/catalog/providers'

export const providerService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedProviders>> {
    return api.get<ApiResponse<PaginatedProviders>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Provider>> {
    return api.get<ApiResponse<Provider>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateProviderInput): Promise<ApiResponse<Provider>> {
    return api.post<ApiResponse<Provider>>(BASE_URL, data)
  },

  update(id: string, data: UpdateProviderInput): Promise<ApiResponse<Provider>> {
    return api.put<ApiResponse<Provider>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },

  testConnection(id: string): Promise<ApiResponse<{ connected: boolean }>> {
    return api.post<ApiResponse<{ connected: boolean }>>(`${BASE_URL}/${id}/test`, {})
  },

  listRemoteProjects(id: string, page = 1, perPage = 20): Promise<ApiResponse<PaginatedRemoteProjects>> {
    return api.get<ApiResponse<PaginatedRemoteProjects>>(`${BASE_URL}/${id}/remote-projects?page=${page}&per_page=${perPage}`)
  },

  importProjects(id: string, data: ImportProjectsInput): Promise<ApiResponse<Project[]>> {
    return api.post<ApiResponse<Project[]>>(`${BASE_URL}/${id}/import`, data)
  },

  syncAll(id: string, force = false): Promise<ApiResponse<{ projectsCount: number, startedAt: string }>> {
    const params = force ? '?force=1' : ''
    return api.post<ApiResponse<{ projectsCount: number, startedAt: string }>>(`${BASE_URL}/${id}/sync-all${params}`, {})
  },

  syncAllGlobal(force = false): Promise<ApiResponse<{ projectsCount: number, startedAt: string }>> {
    const params = force ? '?force=1' : ''
    return api.post<ApiResponse<{ projectsCount: number, startedAt: string }>>(`/catalog/sync-all${params}`, {})
  },
}
