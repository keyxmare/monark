import type { ApiResponse } from '@/shared/types'
import type { CreateProjectInput, Project, ScanResult, UpdateProjectInput } from '@/catalog/types/project'
import { api } from '@/shared/utils/api'

interface PaginatedProjects {
  items: Project[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/catalog/projects'

export const projectService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedProjects>> {
    return api.get<ApiResponse<PaginatedProjects>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Project>> {
    return api.get<ApiResponse<Project>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateProjectInput): Promise<ApiResponse<Project>> {
    return api.post<ApiResponse<Project>>(BASE_URL, data)
  },

  update(id: string, data: UpdateProjectInput): Promise<ApiResponse<Project>> {
    return api.put<ApiResponse<Project>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },

  scan(id: string): Promise<ApiResponse<ScanResult>> {
    return api.post<ApiResponse<ScanResult>>(`${BASE_URL}/${id}/scan`, {})
  },
}
