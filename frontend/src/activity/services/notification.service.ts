import type { ApiResponse } from '@/shared/types'
import type { CreateNotificationInput, Notification } from '@/activity/types/notification'
import { api } from '@/shared/utils/api'

interface PaginatedNotifications {
  items: Notification[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/activity/notifications'

export const notificationService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedNotifications>> {
    return api.get<ApiResponse<PaginatedNotifications>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Notification>> {
    return api.get<ApiResponse<Notification>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateNotificationInput): Promise<ApiResponse<Notification>> {
    return api.post<ApiResponse<Notification>>(BASE_URL, data)
  },

  markAsRead(id: string): Promise<ApiResponse<Notification>> {
    return api.put<ApiResponse<Notification>>(`${BASE_URL}/${id}`, {})
  },
}
