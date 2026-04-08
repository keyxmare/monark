import type { ApiResponse } from '@/shared/types';
import type { CrudService, PaginatedData } from '@/shared/types/crud';
import { api } from '@/shared/utils/api';

export function createCrudService<T, TCreate = Partial<T>, TUpdate = Partial<T>>(
  basePath: string,
): CrudService<T, TCreate, TUpdate> {
  return {
    list(page = 1, perPage = 20): Promise<ApiResponse<PaginatedData<T>>> {
      return api.get<ApiResponse<PaginatedData<T>>>(`${basePath}?page=${page}&per_page=${perPage}`);
    },

    get(id: string): Promise<ApiResponse<T>> {
      return api.get<ApiResponse<T>>(`${basePath}/${id}`);
    },

    create(data: TCreate): Promise<ApiResponse<T>> {
      return api.post<ApiResponse<T>>(basePath, data);
    },

    update(id: string, data: TUpdate): Promise<ApiResponse<T>> {
      return api.put<ApiResponse<T>>(`${basePath}/${id}`, data);
    },

    remove(id: string): Promise<void> {
      return api.delete<void>(`${basePath}/${id}`);
    },
  };
}
