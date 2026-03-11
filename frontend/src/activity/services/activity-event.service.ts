import type { ApiResponse } from '@/shared/types'
import type { ActivityEvent, CreateActivityEventInput } from '@/activity/types/activity-event'
import { api } from '@/shared/utils/api'

interface PaginatedActivityEvents {
  items: ActivityEvent[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/activity/events'

export const activityEventService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedActivityEvents>> {
    return api.get<ApiResponse<PaginatedActivityEvents>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<ActivityEvent>> {
    return api.get<ApiResponse<ActivityEvent>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateActivityEventInput): Promise<ApiResponse<ActivityEvent>> {
    return api.post<ApiResponse<ActivityEvent>>(BASE_URL, data)
  },
}
