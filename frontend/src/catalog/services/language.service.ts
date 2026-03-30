import type { ApiResponse } from '@/shared/types';
import type { PaginatedData } from '@/shared/types/crud';
import type { CreateLanguageInput, Language } from '@/catalog/types/language';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/languages';
const crud = createCrudService<Language, CreateLanguageInput, never>(BASE_URL);

export const languageService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string): Promise<ApiResponse<PaginatedData<Language>>> {
    let url = `${BASE_URL}?page=${page}&per_page=${perPage}`;
    if (projectId) url += `&project_id=${projectId}`;
    return api.get<ApiResponse<PaginatedData<Language>>>(url);
  },
};
