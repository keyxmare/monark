import type { CoverageDashboard, ProjectCoverageHistory } from '@/apps/monitoring/coverage/types';
import type { ApiResponse } from '@/hub/shared/types';
import { api } from '@/hub/shared/utils/api';

export const coverageService = {
  async getDashboard(): Promise<CoverageDashboard> {
    const response = await api.get<ApiResponse<CoverageDashboard>>('/monitoring/coverage');
    return response.data;
  },

  async getProjectHistory(projectSlug: string): Promise<ProjectCoverageHistory> {
    const response = await api.get<ApiResponse<ProjectCoverageHistory>>(`/monitoring/coverage/${projectSlug}`);
    return response.data;
  },
};
