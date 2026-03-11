import type { ApiResponse } from '@/shared/types'
import type { Answer, CreateAnswerInput, UpdateAnswerInput } from '@/assessment/types/answer'
import { api } from '@/shared/utils/api'

interface PaginatedAnswers {
  items: Answer[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/assessment/answers'

export const answerService = {
  list(page = 1, perPage = 20, questionId?: string): Promise<ApiResponse<PaginatedAnswers>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`
    if (questionId) {
      url += `&question_id=${questionId}`
    }
    return api.get<ApiResponse<PaginatedAnswers>>(url)
  },

  get(id: string): Promise<ApiResponse<Answer>> {
    return api.get<ApiResponse<Answer>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateAnswerInput): Promise<ApiResponse<Answer>> {
    return api.post<ApiResponse<Answer>>(BASE_URL, data)
  },

  update(id: string, data: UpdateAnswerInput): Promise<ApiResponse<Answer>> {
    return api.put<ApiResponse<Answer>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
