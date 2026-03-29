import type { ApiResponse } from '@/shared/types';

export interface PaginatedData<T> {
  items: T[];
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
}

export interface CrudService<T, TCreate = Partial<T>, TUpdate = Partial<T>> {
  list(page?: number, perPage?: number): Promise<ApiResponse<PaginatedData<T>>>;
  get(id: string): Promise<ApiResponse<T>>;
  create(data: TCreate): Promise<ApiResponse<T>>;
  update(id: string, data: TUpdate): Promise<ApiResponse<T>>;
  remove(id: string): Promise<void>;
}
