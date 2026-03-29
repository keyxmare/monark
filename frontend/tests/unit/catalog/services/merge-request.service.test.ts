import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { mergeRequestService } from '@/catalog/services/merge-request.service';

describe('mergeRequestService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET with projectId and default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await mergeRequestService.list('proj-1');
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    expect(url).toContain('/catalog/projects/proj-1/merge-requests?');
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('page')).toBe('1');
    expect(params.get('per_page')).toBe('20');
  });

  it('list calls GET with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await mergeRequestService.list('proj-1', 3, 10);
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('page')).toBe('3');
    expect(params.get('per_page')).toBe('10');
  });

  it('list includes status filter when provided', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await mergeRequestService.list('proj-1', 1, 20, 'merged');
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('status')).toBe('merged');
  });

  it('list includes author filter when provided', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await mergeRequestService.list('proj-1', 1, 20, undefined, 'john');
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('author')).toBe('john');
    expect(params.has('status')).toBe(false);
  });

  it('list includes both status and author filters', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await mergeRequestService.list('proj-1', 1, 20, 'open', 'jane');
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('status')).toBe('open');
    expect(params.get('author')).toBe('jane');
  });

  it('get calls GET /catalog/merge-requests/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'mr-1' }, status: 200 });
    await mergeRequestService.get('mr-1');
    expect(api.get).toHaveBeenCalledWith('/catalog/merge-requests/mr-1');
  });
});
