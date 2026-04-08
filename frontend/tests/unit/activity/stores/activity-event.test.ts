import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/activity/services/activity-event.service', () => ({
  activityEventService: {
    list: vi.fn(),
    get: vi.fn(),
  },
}));

import { activityEventService } from '@/activity/services/activity-event.service';
import { useActivityEventStore } from '@/activity/stores/activity-event';

const mockEvent = {
  id: 'evt-1',
  type: 'project_scanned',
  payload: {},
  createdAt: '2026-04-19T00:00:00Z',
};

describe('ActivityEvent Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches paginated events', async () => {
    vi.mocked(activityEventService.list).mockResolvedValue({
      data: { items: [mockEvent], total: 1, page: 1, per_page: 20, total_pages: 1 },
      status: 200,
    });

    const store = useActivityEventStore();
    await store.fetchAll();

    expect(store.events).toHaveLength(1);
    expect(store.total).toBe(1);
    expect(store.loading).toBe(false);
  });

  it('sets error on fetchAll failure', async () => {
    vi.mocked(activityEventService.list).mockRejectedValue(new Error('x'));

    const store = useActivityEventStore();
    await store.fetchAll();

    expect(store.error).toBeTruthy();
  });

  it('fetches a single event', async () => {
    vi.mocked(activityEventService.get).mockResolvedValue({
      data: mockEvent,
      status: 200,
    });

    const store = useActivityEventStore();
    await store.fetchOne('evt-1');

    expect(store.selectedEvent).toEqual(mockEvent);
  });

  it('sets error on fetchOne failure', async () => {
    vi.mocked(activityEventService.get).mockRejectedValue(new Error('x'));

    const store = useActivityEventStore();
    await store.fetchOne('evt-1');

    expect(store.error).toBeTruthy();
  });
});
