import type { CoverageDashboard, ProjectCoverageHistory } from '@/coverage/types';
import { api } from '@/shared/utils/api';

export const coverageService = {
  async getDashboard(): Promise<CoverageDashboard> {
    return api.get<CoverageDashboard>('/coverage');
  },

  async getProjectHistory(projectSlug: string): Promise<ProjectCoverageHistory> {
    return api.get<ProjectCoverageHistory>(`/coverage/${projectSlug}`);
  },
};
