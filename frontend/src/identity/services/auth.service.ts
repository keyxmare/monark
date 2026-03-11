import type { ApiResponse } from '@/shared/types'
import type { AuthTokenResponse, CreateUserInput, User } from '@/identity/types/user'
import { api } from '@/shared/utils/api'

export const authService = {
  register(data: CreateUserInput): Promise<ApiResponse<User>> {
    return api.post<ApiResponse<User>>('/auth/register', data)
  },

  login(email: string, password: string): Promise<ApiResponse<AuthTokenResponse>> {
    return api.post<ApiResponse<AuthTokenResponse>>('/auth/login', { email, password })
  },

  logout(): Promise<void> {
    return api.post<void>('/auth/logout', {})
  },

  getCurrentUser(): Promise<ApiResponse<User>> {
    return api.get<ApiResponse<User>>('/auth/profile')
  },
}
