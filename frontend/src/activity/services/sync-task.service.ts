import type { ApiResponse } from '@/shared/types'
import { api } from '@/shared/utils/api'
import type { SyncTask, SyncTaskStats } from '@/activity/types/sync-task'

interface PaginatedSyncTasks {
  items: SyncTask[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

export interface SyncTaskFilters {
  status?: string
  type?: string
  severity?: string
  projectId?: string
  page?: number
  perPage?: number
}

const BASE_URL = '/activity/sync-tasks'

export const syncTaskService = {
  list(filters: SyncTaskFilters = {}): Promise<ApiResponse<PaginatedSyncTasks>> {
    const params = new URLSearchParams()
    if (filters.status) params.set('status', filters.status)
    if (filters.type) params.set('type', filters.type)
    if (filters.severity) params.set('severity', filters.severity)
    if (filters.projectId) params.set('project_id', filters.projectId)
    params.set('page', String(filters.page ?? 1))
    params.set('per_page', String(filters.perPage ?? 20))

    return api.get<ApiResponse<PaginatedSyncTasks>>(`${BASE_URL}?${params.toString()}`)
  },

  getStats(): Promise<ApiResponse<SyncTaskStats>> {
    return api.get<ApiResponse<SyncTaskStats>>(`${BASE_URL}/stats`)
  },

  updateStatus(id: string, status: string): Promise<ApiResponse<SyncTask>> {
    return api.patch<ApiResponse<SyncTask>>(`${BASE_URL}/${id}`, { status })
  },
}
