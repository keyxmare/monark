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
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedDependencies>> {
    return api.get<ApiResponse<PaginatedDependencies>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
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
