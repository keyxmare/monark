import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { syncTaskService } from '@/activity/services/sync-task.service';

describe('syncTaskService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /activity/sync-tasks with default filters', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await syncTaskService.list();
    expect(api.get).toHaveBeenCalledWith('/activity/sync-tasks?page=1&per_page=20');
  });

  it('list calls GET with all filters', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await syncTaskService.list({
      status: 'pending',
      type: 'dependency',
      severity: 'high',
      projectId: 'p1',
      page: 2,
      perPage: 10,
    });
    expect(api.get).toHaveBeenCalledWith(
      '/activity/sync-tasks?status=pending&type=dependency&severity=high&project_id=p1&page=2&per_page=10',
    );
  });

  it('list calls GET with partial filters', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await syncTaskService.list({ status: 'running' });
    expect(api.get).toHaveBeenCalledWith('/activity/sync-tasks?status=running&page=1&per_page=20');
  });

  it('getStats calls GET /activity/sync-tasks/stats', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await syncTaskService.getStats();
    expect(api.get).toHaveBeenCalledWith('/activity/sync-tasks/stats');
  });

  it('updateStatus calls PATCH /activity/sync-tasks/:id', async () => {
    vi.mocked(api.patch).mockResolvedValue({ data: {}, status: 200 });
    await syncTaskService.updateStatus('task-1', 'completed');
    expect(api.patch).toHaveBeenCalledWith('/activity/sync-tasks/task-1', {
      status: 'completed',
    });
  });
});
