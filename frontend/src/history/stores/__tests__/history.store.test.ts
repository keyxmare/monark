import { setActivePinia, createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { historyService } from '@/history/services/history.service';
import { useHistoryStore } from '@/history/stores/history';

vi.mock('@/history/services/history.service');

describe('useHistoryStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('loads timeline points from the service', async () => {
    vi.mocked(historyService.getTimeline).mockResolvedValue({
      data: [
        {
          snapshotId: 'snap-1',
          commitSha: 'abc',
          snapshotDate: '2025-01-01T00:00:00+00:00',
          source: 'live',
          totalDeps: 5,
          outdatedCount: 2,
          vulnerableCount: 0,
          majorGapCount: 1,
          minorGapCount: 1,
          patchGapCount: 0,
          ltsGapCount: 0,
          debtScore: 1.4,
        },
      ],
    } as never);

    const store = useHistoryStore();
    await store.loadTimeline('proj-1');

    expect(historyService.getTimeline).toHaveBeenCalledWith('proj-1', undefined, undefined);
    expect(store.timeline).toHaveLength(1);
    expect(store.timeline[0].snapshotId).toBe('snap-1');
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it('sets error on failed timeline fetch', async () => {
    vi.mocked(historyService.getTimeline).mockRejectedValue(new Error('boom'));

    const store = useHistoryStore();
    await store.loadTimeline('proj-1');

    expect(store.timeline).toHaveLength(0);
    expect(store.error).toBe('Failed to load debt timeline');
  });

  it('marks backfill as scheduled on success', async () => {
    vi.mocked(historyService.triggerBackfill).mockResolvedValue({
      data: { scheduled: true },
    } as never);

    const store = useHistoryStore();
    await store.triggerBackfill('proj-1', {
      since: '2025-01-01T00:00:00.000Z',
      until: '2026-01-01T00:00:00.000Z',
      intervalDays: 30,
    });

    expect(store.backfillScheduled).toBe(true);
  });

  it('reset clears state', async () => {
    vi.mocked(historyService.getTimeline).mockResolvedValue({
      data: [
        {
          snapshotId: 's1',
          commitSha: 'a',
          snapshotDate: '2025-01-01T00:00:00+00:00',
          source: 'live',
          totalDeps: 0,
          outdatedCount: 0,
          vulnerableCount: 0,
          majorGapCount: 0,
          minorGapCount: 0,
          patchGapCount: 0,
          ltsGapCount: 0,
          debtScore: 0,
        },
      ],
    } as never);
    const store = useHistoryStore();
    await store.loadTimeline('proj-1');
    store.reset();
    expect(store.timeline).toHaveLength(0);
    expect(store.backfillScheduled).toBe(false);
  });
});
