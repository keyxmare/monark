import type { ApiResponse } from '@/shared/types'
import type { MergeRequest } from '@/catalog/types/merge-request'
import { api } from '@/shared/utils/api'

interface PaginatedMergeRequests {
  items: MergeRequest[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

export const mergeRequestService = {
  list(projectId: string, page = 1, perPage = 20, status?: string, author?: string): Promise<ApiResponse<PaginatedMergeRequests>> {
    const params = new URLSearchParams()
    params.set('page', String(page))
    params.set('per_page', String(perPage))
    if (status) params.set('status', status)
    if (author) params.set('author', author)
    return api.get<ApiResponse<PaginatedMergeRequests>>(`/catalog/projects/${projectId}/merge-requests?${params}`)
  },

  get(id: string): Promise<ApiResponse<MergeRequest>> {
    return api.get<ApiResponse<MergeRequest>>(`/catalog/merge-requests/${id}`)
  },
}
