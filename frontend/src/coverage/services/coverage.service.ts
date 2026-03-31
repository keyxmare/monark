import type { CoverageDashboard, ProjectCoverageHistory } from '@/coverage/types';
import { api } from '@/shared/utils/api';

export const coverageService = {
  async getDashboard(): Promise<CoverageDashboard> {
    const res = await api.get<{ data: CoverageDashboard }>('/coverage');
    return res.data;
  },

  async getProjectHistory(projectSlug: string): Promise<ProjectCoverageHistory> {
    const res = await api.get<{ data: ProjectCoverageHistory }>(`/coverage/${projectSlug}`);
    return res.data;
  },
};
