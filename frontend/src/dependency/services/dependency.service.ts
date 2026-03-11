import type { ApiResponse } from '@/shared/types'
import type { CreateDependencyInput, Dependency, UpdateDependencyInput } from '@/dependency/types/dependency'
import { api } from '@/shared/utils/api'

interface PaginatedDependencies {
  items: Dependency[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/dependency/dependencies'

export const dependencyService = {
  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedDependencies>> {
    const params = new URLSearchParams({ page: String(page), per_page: String(perPage) })
    if (projectId) params.set('project_id', projectId)
    return api.get<ApiResponse<PaginatedDependencies>>(`${BASE_URL}?${params}`)
  },

  get(id: string): Promise<ApiResponse<Dependency>> {
    return api.get<ApiResponse<Dependency>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateDependencyInput): Promise<ApiResponse<Dependency>> {
    return api.post<ApiResponse<Dependency>>(BASE_URL, data)
  },

  update(id: string, data: UpdateDependencyInput): Promise<ApiResponse<Dependency>> {
    return api.put<ApiResponse<Dependency>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
