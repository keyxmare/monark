import type { ApiResponse } from '@/shared/types';
import type { UpdateUserInput, User } from '@/identity/types/user';
import { api } from '@/shared/utils/api';

interface PaginatedUsers {
  items: User[];
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

const BASE_URL = '/identity/users';

export const userService = {
  list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedUsers>> {
    return api.get<ApiResponse<PaginatedUsers>>(`${BASE_URL}?page=${page}&per_page=${perPage}`);
  },

  get(id: string): Promise<ApiResponse<User>> {
    return api.get<ApiResponse<User>>(`${BASE_URL}/${id}`);
  },

  update(id: string, data: UpdateUserInput): Promise<ApiResponse<User>> {
    return api.put<ApiResponse<User>>(`${BASE_URL}/${id}`, data);
  },
};
