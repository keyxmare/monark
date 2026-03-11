import type { ApiResponse } from '@/shared/types'
import type { CreatePipelineInput, Pipeline } from '@/catalog/types/pipeline'
import { api } from '@/shared/utils/api'

interface PaginatedPipelines {
  items: Pipeline[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/catalog/pipelines'

export const pipelineService = {
  list(page = 1, perPage = 20, projectId?: string, ref?: string): Promise<ApiResponse<PaginatedPipelines>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`
    if (projectId) {
      url += `&project_id=${projectId}`
    }
    if (ref) {
      url += `&ref=${encodeURIComponent(ref)}`
    }
    return api.get<ApiResponse<PaginatedPipelines>>(url)
  },

  get(id: string): Promise<ApiResponse<Pipeline>> {
    return api.get<ApiResponse<Pipeline>>(`${BASE_URL}/${id}`)
  },

  create(data: CreatePipelineInput): Promise<ApiResponse<Pipeline>> {
    return api.post<ApiResponse<Pipeline>>(BASE_URL, data)
  },
}
