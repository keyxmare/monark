import type { ApiResponse } from '@/shared/types';
import type { CreateTechStackInput, TechStack } from '@/catalog/types/tech-stack';
import { api } from '@/shared/utils/api';

interface PaginatedTechStacks {
  items: TechStack[];
  total: number;
  page: number;
  per_page: number;
  total_pages: number;
}

const BASE_URL = '/catalog/tech-stacks';

export const techStackService = {
  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedTechStacks>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) {
      url += `&project_id=${projectId}`;
    }
    return api.get<ApiResponse<PaginatedTechStacks>>(url);
  },

  get(id: string): Promise<ApiResponse<TechStack>> {
    return api.get<ApiResponse<TechStack>>(`${BASE_URL}/${id}`);
  },

  create(data: CreateTechStackInput): Promise<ApiResponse<TechStack>> {
    return api.post<ApiResponse<TechStack>>(BASE_URL, data);
  },

  remove(id: string): Promise<void> {
    return api.delete<void>(`${BASE_URL}/${id}`);
  },
};
