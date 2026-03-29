import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { notificationService } from '@/activity/services/notification.service';

describe('notificationService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /activity/notifications with default params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await notificationService.list();
    expect(api.get).toHaveBeenCalledWith(
      '/activity/notifications?page=1&per_page=20',
    );
  });

  it('list calls GET with custom page and perPage', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await notificationService.list(4, 10);
    expect(api.get).toHaveBeenCalledWith(
      '/activity/notifications?page=4&per_page=10',
    );
  });

  it('get calls GET /activity/notifications/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await notificationService.get('notif-1');
    expect(api.get).toHaveBeenCalledWith('/activity/notifications/notif-1');
  });

  it('create calls POST /activity/notifications', async () => {
    const input = { title: 'Alert', message: 'New vulnerability found' };
    vi.mocked(api.post).mockResolvedValue({ data: {}, status: 201 });
    await notificationService.create(input as never);
    expect(api.post).toHaveBeenCalledWith('/activity/notifications', input);
  });

  it('markAsRead calls PUT /activity/notifications/:id', async () => {
    vi.mocked(api.put).mockResolvedValue({ data: {}, status: 200 });
    await notificationService.markAsRead('notif-1');
    expect(api.put).toHaveBeenCalledWith('/activity/notifications/notif-1', {});
  });
});
