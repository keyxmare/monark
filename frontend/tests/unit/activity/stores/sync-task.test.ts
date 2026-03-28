import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useSyncTaskStore } from '@/activity/stores/sync-task';

vi.mock('@/activity/services/sync-task.service', () => ({
  syncTaskService: {
    list: vi.fn(),
    getStats: vi.fn(),
    updateStatus: vi.fn(),
  },
}));

import { syncTaskService } from '@/activity/services/sync-task.service';

const mockTask = {
  id: '1',
  type: 'vulnerability',
  severity: 'critical',
  title: 'CVE-2026-1234',
  description: 'Critical vulnerability in lodash',
  status: 'open',
  metadata: {},
  projectId: 'proj-1',
  resolvedAt: null,
  createdAt: '2026-03-12T00:00:00+00:00',
  updatedAt: '2026-03-12T00:00:00+00:00',
};

describe('SyncTask Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches sync tasks with filters', async () => {
    vi.mocked(syncTaskService.list).mockResolvedValue({
      data: {
        items: [mockTask],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useSyncTaskStore();
    await store.fetchAll({ status: 'open', severity: 'critical' });

    expect(syncTaskService.list).toHaveBeenCalledWith({ status: 'open', severity: 'critical' });
    expect(store.tasks).toHaveLength(1);
    expect(store.tasks[0].title).toBe('CVE-2026-1234');
    expect(store.total).toBe(1);
    expect(store.loading).toBe(false);
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(syncTaskService.list).mockRejectedValue(new Error('Network error'));

    const store = useSyncTaskStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load sync tasks');
    expect(store.loading).toBe(false);
  });

  it('fetches stats', async () => {
    vi.mocked(syncTaskService.getStats).mockResolvedValue({
      data: {
        byType: [{ label: 'vulnerability', count: 3 }],
        bySeverity: [
          { label: 'critical', count: 2 },
          { label: 'high', count: 1 },
        ],
        byStatus: [{ label: 'open', count: 3 }],
      },
      status: 200,
    });

    const store = useSyncTaskStore();
    await store.fetchStats();

    expect(store.stats).not.toBeNull();
    expect(store.stats!.bySeverity).toHaveLength(2);
    expect(store.stats!.bySeverity[0].count).toBe(2);
  });

  it('updates task status in-place', async () => {
    const updatedTask = { ...mockTask, status: 'acknowledged' };
    vi.mocked(syncTaskService.list).mockResolvedValue({
      data: { items: [mockTask], total: 1, page: 1, per_page: 20, total_pages: 1 },
      status: 200,
    });
    vi.mocked(syncTaskService.updateStatus).mockResolvedValue({
      data: updatedTask,
      status: 200,
    });

    const store = useSyncTaskStore();
    await store.fetchAll();
    await store.updateStatus('1', 'acknowledged');

    expect(syncTaskService.updateStatus).toHaveBeenCalledWith('1', 'acknowledged');
    expect(store.tasks[0].status).toBe('acknowledged');
  });

  it('sets error on update failure', async () => {
    vi.mocked(syncTaskService.updateStatus).mockRejectedValue(new Error('Fail'));

    const store = useSyncTaskStore();
    await store.updateStatus('1', 'resolved');

    expect(store.error).toBe('Failed to update sync tasks');
  });
});
