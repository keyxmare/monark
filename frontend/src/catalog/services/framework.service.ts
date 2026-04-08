import type { ApiResponse } from '@/shared/types';
import type { PaginatedData } from '@/shared/types/crud';
import type { CreateFrameworkInput, Framework } from '@/catalog/types/framework';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/frameworks';
const crud = createCrudService<Framework, CreateFrameworkInput, never>(BASE_URL);

export const frameworkService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedData<Framework>>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) url += `&project_id=${projectId}`;
    return api.get<ApiResponse<PaginatedData<Framework>>>(url);
  },
};
