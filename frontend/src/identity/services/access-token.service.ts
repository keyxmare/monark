import type { ApiResponse } from '@/shared/types'
import type { AccessToken, CreateAccessTokenInput } from '@/identity/types/access-token'
import { api } from '@/shared/utils/api'

interface PaginatedAccessTokens {
  items: AccessToken[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/identity/access-tokens'

export const accessTokenService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedAccessTokens>> {
    return api.get<ApiResponse<PaginatedAccessTokens>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<AccessToken>> {
    return api.get<ApiResponse<AccessToken>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateAccessTokenInput): Promise<ApiResponse<AccessToken>> {
    return api.post<ApiResponse<AccessToken>>(BASE_URL, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
