import type { ApiResponse } from '@/hub/shared/types';
import type { AuthTokenResponse, CreateUserInput, User } from '@/hub/identity/types/user';
import { api } from '@/hub/shared/utils/api';

export const authService = {
  register(data: CreateUserInput): Promise<ApiResponse<User>> {
    return api.post<ApiResponse<User>>('/hub/auth/register', data);
  },

  login(email: string, password: string): Promise<ApiResponse<AuthTokenResponse>> {
    return api.post<ApiResponse<AuthTokenResponse>>('/hub/auth/login', { email, password });
  },

  logout(): Promise<void> {
    return api.post<void>('/hub/auth/logout', {});
  },

  getCurrentUser(): Promise<ApiResponse<User>> {
    return api.get<ApiResponse<User>>('/hub/auth/profile');
  },
};
