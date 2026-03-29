import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { messengerService } from '@/activity/services/messenger.service';

describe('messengerService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getStats calls GET /activity/messenger/stats', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await messengerService.getStats();
    expect(api.get).toHaveBeenCalledWith('/activity/messenger/stats');
  });
});
