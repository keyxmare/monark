import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/activity/services/messenger.service', () => ({
  messengerService: {
    getStats: vi.fn(),
  },
}));

vi.mock('@/shared/i18n', () => ({
  i18n: {
    global: {
      t: (key: string, params?: Record<string, string>) => key,
    },
  },
}));

import { messengerService } from '@/activity/services/messenger.service';
import { useMessengerStore } from '@/activity/stores/messenger';

describe('messenger store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetchStats populates queues and workers', async () => {
    vi.mocked(messengerService.getStats).mockResolvedValue({
      data: {
        queues: [
          {
            name: 'async',
            messages: 10,
            messages_ready: 8,
            messages_unacknowledged: 2,
            consumers: 1,
            publish_rate: 5.0,
            deliver_rate: 4.5,
          },
        ],
        workers: [
          {
            connection: 'amqp://localhost:5672',
            prefetch: 10,
            state: 'running',
          },
        ],
      },
    } as never);

    const store = useMessengerStore();
    await store.fetchStats();

    expect(store.queues).toHaveLength(1);
    expect(store.queues[0].name).toBe('async');
    expect(store.workers).toHaveLength(1);
    expect(store.workers[0].state).toBe('running');
    expect(store.error).toBeNull();
    expect(store.loading).toBe(false);
  });

  it('fetchStats handles error', async () => {
    vi.mocked(messengerService.getStats).mockRejectedValue(new Error('Network error'));

    const store = useMessengerStore();
    await store.fetchStats();

    expect(store.queues).toHaveLength(0);
    expect(store.workers).toHaveLength(0);
    expect(store.error).toBe('common.errors.failedToLoad');
    expect(store.loading).toBe(false);
  });

  it('fetchStats sets loading during request', async () => {
    let resolve: (v: unknown) => void;
    const promise = new Promise((r) => {
      resolve = r;
    });
    vi.mocked(messengerService.getStats).mockReturnValue(promise as never);

    const store = useMessengerStore();
    const fetchPromise = store.fetchStats();

    expect(store.loading).toBe(true);

    resolve!({ data: { queues: [], workers: [] } });
    await fetchPromise;

    expect(store.loading).toBe(false);
  });
});
