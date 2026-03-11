import type { ApiResponse } from '@/shared/types'
import type { CreateQuestionInput, Question, UpdateQuestionInput } from '@/assessment/types/question'
import { api } from '@/shared/utils/api'

interface PaginatedQuestions {
  items: Question[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/assessment/questions'

export const questionService = {
  list(page = 1, perPage = 20, quizId?: string): Promise<ApiResponse<PaginatedQuestions>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`
    if (quizId) {
      url += `&quiz_id=${quizId}`
    }
    return api.get<ApiResponse<PaginatedQuestions>>(url)
  },

  get(id: string): Promise<ApiResponse<Question>> {
    return api.get<ApiResponse<Question>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateQuestionInput): Promise<ApiResponse<Question>> {
    return api.post<ApiResponse<Question>>(BASE_URL, data)
  },

  update(id: string, data: UpdateQuestionInput): Promise<ApiResponse<Question>> {
    return api.put<ApiResponse<Question>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
