import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useDashboardStore } from '@/apps/monitoring/activity/stores/dashboard';

vi.mock('@/apps/monitoring/activity/services/dashboard.service', () => ({
  dashboardService: {
    getDashboard: vi.fn(),
  },
}));

import { dashboardService } from '@/apps/monitoring/activity/services/dashboard.service';

describe('Dashboard Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('loads the dashboard summary', async () => {
    vi.mocked(dashboardService.getDashboard).mockResolvedValue({
      projects: 19,
      commits30d: 120,
      activeBranches30d: 7,
      dependenciesTracked: 1088,
      vulnerabilities: 132,
      coveragePercent: 74.5,
      languages: [],
      hosts: [],
    });

    const store = useDashboardStore();
    await store.load();

    expect(store.summary).toEqual({
      projects: 19,
      commits30d: 120,
      activeBranches30d: 7,
      dependenciesTracked: 1088,
      vulnerabilities: 132,
      coveragePercent: 74.5,
      languages: [],
      hosts: [],
    });
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it('sets error on load failure', async () => {
    vi.mocked(dashboardService.getDashboard).mockRejectedValue(new Error('Network error'));

    const store = useDashboardStore();
    await store.load();

    expect(store.error).toBe('Failed to load dashboard');
    expect(store.summary).toBeNull();
    expect(store.loading).toBe(false);
  });
});
