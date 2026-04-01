import type { CoverageDashboard, ProjectCoverageHistory } from '@/coverage/types';
import type { ApiResponse } from '@/shared/types';
import { api } from '@/shared/utils/api';

export const coverageService = {
  async getDashboard(): Promise<CoverageDashboard> {
    const response = await api.get<ApiResponse<CoverageDashboard>>('/coverage');
    return response.data;
  },

  async getProjectHistory(projectSlug: string): Promise<ProjectCoverageHistory> {
    const response = await api.get<ApiResponse<ProjectCoverageHistory>>(`/coverage/${projectSlug}`);
    return response.data;
  },
};
