import type { ApiResponse } from '@/shared/types'
import type { Attempt, CreateAttemptInput } from '@/assessment/types/attempt'
import { api } from '@/shared/utils/api'

interface PaginatedAttempts {
  items: Attempt[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/assessment/attempts'

export const attemptService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedAttempts>> {
    return api.get<ApiResponse<PaginatedAttempts>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Attempt>> {
    return api.get<ApiResponse<Attempt>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateAttemptInput): Promise<ApiResponse<Attempt>> {
    return api.post<ApiResponse<Attempt>>(BASE_URL, data)
  },
}
