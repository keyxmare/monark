import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { dashboardService } from '@/activity/services/dashboard.service';

describe('dashboardService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getDashboard calls GET /activity/dashboard', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { metrics: [] }, status: 200 });
    await dashboardService.getDashboard();
    expect(api.get).toHaveBeenCalledWith('/activity/dashboard');
  });
});
