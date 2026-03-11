import type { ApiResponse } from '@/shared/types'
import type { CreateQuizInput, Quiz, UpdateQuizInput } from '@/assessment/types/quiz'
import { api } from '@/shared/utils/api'

interface PaginatedQuizzes {
  items: Quiz[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/assessment/quizzes'

export const quizService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedQuizzes>> {
    return api.get<ApiResponse<PaginatedQuizzes>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Quiz>> {
    return api.get<ApiResponse<Quiz>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateQuizInput): Promise<ApiResponse<Quiz>> {
    return api.post<ApiResponse<Quiz>>(BASE_URL, data)
  },

  update(id: string, data: UpdateQuizInput): Promise<ApiResponse<Quiz>> {
    return api.put<ApiResponse<Quiz>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
