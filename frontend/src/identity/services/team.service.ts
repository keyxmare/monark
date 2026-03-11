import type { ApiResponse } from '@/shared/types'
import type { CreateTeamInput, Team, UpdateTeamInput } from '@/identity/types/team'
import { api } from '@/shared/utils/api'

interface PaginatedTeams {
  items: Team[]
  total: number
  page: number
  per_page: number
  total_pages: number
}

const BASE_URL = '/identity/teams'

export const teamService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedTeams>> {
    return api.get<ApiResponse<PaginatedTeams>>(`${BASE_URL}?page=${page}&per_page=${perPage}`)
  },

  get(id: string): Promise<ApiResponse<Team>> {
    return api.get<ApiResponse<Team>>(`${BASE_URL}/${id}`)
  },

  create(data: CreateTeamInput): Promise<ApiResponse<Team>> {
    return api.post<ApiResponse<Team>>(BASE_URL, data)
  },

  update(id: string, data: UpdateTeamInput): Promise<ApiResponse<Team>> {
    return api.put<ApiResponse<Team>>(`${BASE_URL}/${id}`, data)
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`)
  },
}
