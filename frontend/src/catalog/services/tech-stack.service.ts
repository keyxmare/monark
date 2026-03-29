import type { ApiResponse } from '@/shared/types';
import type { PaginatedData } from '@/shared/types/crud';
import type { CreateTechStackInput, TechStack } from '@/catalog/types/tech-stack';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/tech-stacks';
const crud = createCrudService<TechStack, CreateTechStackInput, never>(BASE_URL);

export const techStackService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedData<TechStack>>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) url += `&project_id=${projectId}`;
    return api.get<ApiResponse<PaginatedData<TechStack>>>(url);
  },
};
