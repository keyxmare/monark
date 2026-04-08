import { describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

import { activityEventService } from '@/activity/services/activity-event.service';
import { api } from '@/shared/utils/api';

describe('activityEventService', () => {
  it('list calls the paginated endpoint', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({
      data: { items: [], total: 0, page: 1, per_page: 20, total_pages: 0 },
    } as never);
    await activityEventService.list(2, 50);
    expect(api.get).toHaveBeenCalledWith('/activity/events?page=2&per_page=50');
  });

  it('get fetches a single event by id', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { id: 'evt-1' } } as never);
    const res = await activityEventService.get('evt-1');
    expect(api.get).toHaveBeenCalledWith('/activity/events/evt-1');
    expect(res.data).toEqual({ id: 'evt-1' });
  });

  it('create posts a new event', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({ data: { id: 'evt-2' } } as never);
    const res = await activityEventService.create({ type: 'x', payload: {} } as never);
    expect(api.post).toHaveBeenCalledWith('/activity/events', { type: 'x', payload: {} });
    expect(res.data).toEqual({ id: 'evt-2' });
  });
});
