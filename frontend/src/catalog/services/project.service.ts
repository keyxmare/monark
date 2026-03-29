import type { ApiResponse } from '@/shared/types';
import type {
  CreateProjectInput,
  Project,
  ScanResult,
  UpdateProjectInput,
} from '@/catalog/types/project';
import { createCrudService } from '@/shared/services/createCrudService';
import { api } from '@/shared/utils/api';

const BASE_URL = '/catalog/projects';

export const projectService = {
  ...createCrudService<Project, CreateProjectInput, UpdateProjectInput>(BASE_URL),

  scan(id: string): Promise<ApiResponse<ScanResult>> {
    return api.post<ApiResponse<ScanResult>>(`${BASE_URL}/${id}/scan`, {});
  },
};
