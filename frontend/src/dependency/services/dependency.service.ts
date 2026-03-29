import type { ApiResponse } from '@/shared/types';
import type {
  CreateDependencyInput,
  Dependency,
  UpdateDependencyInput,
} from '@/dependency/types/dependency';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/dependency/dependencies';

const crud = createCrudService<Dependency, CreateDependencyInput, UpdateDependencyInput>(BASE_URL);

export const dependencyService = {
  ...crud,

  list(page = 1, perPage = 20, projectId?: string) {
    const params = new URLSearchParams({ page: String(page), per_page: String(perPage) });
    if (projectId) params.set('project_id', projectId);
    return api.get<
      ApiResponse<{
        items: Dependency[];
        total: number;
        page: number;
        per_page: number;
        total_pages: number;
      }>
    >(`${BASE_URL}?${params}`);
  },

  sync(): Promise<ApiResponse<{ syncId: string }>> {
    return api.post<ApiResponse<{ syncId: string }>>('/dependency/sync', {});
  },

  stats(params?: {
    projectId?: string;
    packageManager?: string;
    type?: string;
  }): Promise<
    ApiResponse<{ total: number; upToDate: number; outdated: number; totalVulnerabilities: number }>
  > {
    const qs = new URLSearchParams();
    if (params?.projectId) qs.set('project_id', params.projectId);
    if (params?.packageManager) qs.set('package_manager', params.packageManager);
    if (params?.type) qs.set('type', params.type);
    const query = qs.toString();
    return api.get<
      ApiResponse<{
        total: number;
        upToDate: number;
        outdated: number;
        totalVulnerabilities: number;
      }>
    >(`/dependency/stats${query ? `?${query}` : ''}`);
  },
};
