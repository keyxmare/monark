import type { ApiResponse } from '@/hub/shared/types';
import type {
  CreateProjectInput,
  Project,
  ScanResult,
  UpdateProjectInput,
} from '@/apps/monitoring/catalog/types/project';
import { createCrudService } from '@/hub/shared/services/createCrudService';
import { api } from '@/hub/shared/utils/api';

const BASE_URL = '/monitoring/catalog/projects';

export const projectService = {
  ...createCrudService<Project, CreateProjectInput, UpdateProjectInput>(BASE_URL),

  scan(id: string): Promise<ApiResponse<ScanResult>> {
    return api.post<ApiResponse<ScanResult>>(`${BASE_URL}/${id}/scan`, {});
  },

  async listBranches(id: string): Promise<string[]> {
    const body = await api.get<ApiResponse<string[]>>(`${BASE_URL}/${id}/branches`);
    return body.data ?? [];
  },
};
