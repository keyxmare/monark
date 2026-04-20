import { describe, expect, it, vi } from 'vitest';

vi.mock('@/hub/shared/utils/api', () => ({
  api: {
    get: vi.fn(),
  },
}));

import { coverageService } from '@/apps/monitoring/coverage/services/coverage.service';
import { api } from '@/hub/shared/utils/api';

describe('coverageService', () => {
  it('getDashboard calls /coverage and returns data', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { global: 80 } } as never);
    const result = await coverageService.getDashboard();
    expect(api.get).toHaveBeenCalledWith('/monitoring/coverage');
    expect(result).toEqual({ global: 80 });
  });

  it('getProjectHistory calls /coverage/:slug and returns data', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { history: [] } } as never);
    const result = await coverageService.getProjectHistory('my-project');
    expect(api.get).toHaveBeenCalledWith('/monitoring/coverage/my-project');
    expect(result).toEqual({ history: [] });
  });
});
