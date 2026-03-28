import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useActivityEventStore } from '@/activity/stores/activity-event';

vi.mock('@/activity/services/activity-event.service', () => ({
  activityEventService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
  },
}));

import { activityEventService } from '@/activity/services/activity-event.service';

const mockEvent = {
  id: '123',
  type: 'project.created',
  entityType: 'Project',
  entityId: 'abc-123',
  payload: { name: 'Test' },
  occurredAt: '2026-01-01T00:00:00+00:00',
  userId: '456',
};

describe('Activity Event Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches all events', async () => {
    vi.mocked(activityEventService.list).mockResolvedValue({
      data: {
        items: [mockEvent],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useActivityEventStore();
    await store.fetchAll();

    expect(store.events).toHaveLength(1);
    expect(store.events[0].type).toBe('project.created');
    expect(store.total).toBe(1);
  });

  it('fetches one event', async () => {
    vi.mocked(activityEventService.get).mockResolvedValue({
      data: mockEvent,
      status: 200,
    });

    const store = useActivityEventStore();
    await store.fetchOne('123');

    expect(store.selectedEvent).not.toBeNull();
    expect(store.selectedEvent!.id).toBe('123');
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(activityEventService.list).mockRejectedValue(new Error('Network error'));

    const store = useActivityEventStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load activity events');
  });

  it('sets error on fetchOne failure', async () => {
    vi.mocked(activityEventService.get).mockRejectedValue(new Error('Not found'));

    const store = useActivityEventStore();
    await store.fetchOne('999');

    expect(store.error).toBe('Failed to load activity events');
  });
});
