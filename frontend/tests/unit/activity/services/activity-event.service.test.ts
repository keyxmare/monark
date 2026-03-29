import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { activityEventService } from '@/activity/services/activity-event.service';

describe('activityEventService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /activity/events with default params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await activityEventService.list();
    expect(api.get).toHaveBeenCalledWith('/activity/events?page=1&per_page=20');
  });

  it('list calls GET with custom page and perPage', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await activityEventService.list(2, 50);
    expect(api.get).toHaveBeenCalledWith('/activity/events?page=2&per_page=50');
  });

  it('get calls GET /activity/events/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await activityEventService.get('evt-1');
    expect(api.get).toHaveBeenCalledWith('/activity/events/evt-1');
  });

  it('create calls POST /activity/events', async () => {
    const input = { type: 'sync', message: 'Sync started' };
    vi.mocked(api.post).mockResolvedValue({ data: {}, status: 201 });
    await activityEventService.create(input as never);
    expect(api.post).toHaveBeenCalledWith('/activity/events', input);
  });
});
