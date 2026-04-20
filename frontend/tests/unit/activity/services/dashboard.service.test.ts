import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/hub/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/hub/shared/utils/api';
import { dashboardService } from '@/apps/monitoring/activity/services/dashboard.service';

describe('dashboardService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getDashboard calls GET /monitoring/activity/dashboard and maps snake_case fields', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: {
        projects: 19,
        commits_30d: 120,
        active_branches_30d: 7,
        dependencies_tracked: 1088,
        vulnerabilities: 132,
        coverage_percent: 74.5,
        coverage_health_percent: 70.5,
        languages: [
          { name: 'PHP', projects_count: 10 },
          { name: 'TypeScript', projects_count: 7 },
        ],
        hosts: [{ type: 'gitlab', label: 'GitLab', connected: true, projects_count: 19 }],
      },
      status: 200,
    });

    const summary = await dashboardService.getDashboard();

    expect(api.get).toHaveBeenCalledWith('/monitoring/activity/dashboard');
    expect(summary).toEqual({
      projects: 19,
      commits30d: 120,
      activeBranches30d: 7,
      dependenciesTracked: 1088,
      vulnerabilities: 132,
      coveragePercent: 74.5,
      coverageHealthPercent: 70.5,
      languages: [
        { name: 'PHP', projectsCount: 10 },
        { name: 'TypeScript', projectsCount: 7 },
      ],
      hosts: [{ type: 'gitlab', label: 'GitLab', connected: true, projectsCount: 19 }],
      mostActive: [],
      weakestCoverage: [],
      topOutdatedDependencies: [],
      projectDrift: [],
    });
  });

  it('preserves null coverage', async () => {
    vi.mocked(api.get).mockResolvedValue({
      data: {
        projects: 0,
        commits_30d: 0,
        active_branches_30d: 0,
        dependencies_tracked: 0,
        vulnerabilities: 0,
        coverage_percent: null,
        languages: [],
        hosts: [],
      },
      status: 200,
    });

    const summary = await dashboardService.getDashboard();
    expect(summary.coveragePercent).toBeNull();
    expect(summary.languages).toEqual([]);
    expect(summary.hosts).toEqual([]);
  });
});
