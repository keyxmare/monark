import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/coverage/services/coverage.service', () => ({
  coverageService: {
    getDashboard: vi.fn(),
  },
}));

import { coverageService } from '@/coverage/services/coverage.service';
import { useCoverageStore } from '@/coverage/stores/coverage';

describe('Coverage Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches dashboard data', async () => {
    const dashboard = { global: 82.5, projects: [] };
    vi.mocked(coverageService.getDashboard).mockResolvedValue(dashboard as never);

    const store = useCoverageStore();
    await store.fetchDashboard();

    expect(store.dashboard).toEqual(dashboard);
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it('captures Error message on failure', async () => {
    vi.mocked(coverageService.getDashboard).mockRejectedValue(new Error('boom'));

    const store = useCoverageStore();
    await store.fetchDashboard();

    expect(store.error).toBe('boom');
  });

  it('falls back to default message on non-Error rejection', async () => {
    vi.mocked(coverageService.getDashboard).mockRejectedValue('oops');

    const store = useCoverageStore();
    await store.fetchDashboard();

    expect(store.error).toBe('Failed to fetch coverage data');
  });
});
