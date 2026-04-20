import { api } from '@/hub/shared/utils/api';

export interface HubSummary {
  reposTracked: number;
  commits30d: number;
  focusSecondsToday: number;
  cveCount: number;
  coveragePercent: null | number;
}

interface HubSummaryResponse {
  data: {
    repos_tracked: number;
    commits_30d: number;
    focus_seconds_today: number;
    cve_count: number;
    coverage_percent: null | number;
  };
}

export const hubService = {
  async getSummary(): Promise<HubSummary> {
    const body = await api.get<HubSummaryResponse>('/hub/summary');
    return {
      reposTracked: body.data.repos_tracked,
      commits30d: body.data.commits_30d,
      focusSecondsToday: body.data.focus_seconds_today,
      cveCount: body.data.cve_count,
      coveragePercent: body.data.coverage_percent,
    };
  },
};
