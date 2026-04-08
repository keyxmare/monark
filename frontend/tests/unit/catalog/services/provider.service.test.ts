import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { providerService } from '@/catalog/services/provider.service';

describe('providerService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /catalog/providers with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.list();
    expect(api.get).toHaveBeenCalledWith('/catalog/providers?page=1&per_page=20');
  });

  it('list calls GET /catalog/providers with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.list(3, 5);
    expect(api.get).toHaveBeenCalledWith('/catalog/providers?page=3&per_page=5');
  });

  it('get calls GET /catalog/providers/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'p1' }, status: 200 });
    await providerService.get('p1');
    expect(api.get).toHaveBeenCalledWith('/catalog/providers/p1');
  });

  it('create calls POST /catalog/providers', async () => {
    const input = { name: 'GitLab', type: 'gitlab', url: 'https://gitlab.com', token: 'tok' };
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'new' }, status: 201 });
    await providerService.create(input as any);
    expect(api.post).toHaveBeenCalledWith('/catalog/providers', input);
  });

  it('update calls PUT /catalog/providers/:id', async () => {
    const input = { name: 'Updated' };
    vi.mocked(api.put).mockResolvedValue({ data: { id: 'p1' }, status: 200 });
    await providerService.update('p1', input as any);
    expect(api.put).toHaveBeenCalledWith('/catalog/providers/p1', input);
  });

  it('remove calls DELETE /catalog/providers/:id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);
    await providerService.remove('p1');
    expect(api.delete).toHaveBeenCalledWith('/catalog/providers/p1');
  });

  it('testConnection calls POST /catalog/providers/:id/test', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'p1' }, status: 200 });
    await providerService.testConnection('p1');
    expect(api.post).toHaveBeenCalledWith('/catalog/providers/p1/test', {});
  });

  it('listRemoteProjects calls GET with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.listRemoteProjects('p1');
    expect(api.get).toHaveBeenCalledWith(
      '/catalog/providers/p1/remote-projects?page=1&per_page=20',
    );
  });

  it('listRemoteProjects calls GET with search and visibility params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.listRemoteProjects('p1', 2, 10, {
      search: 'myrepo',
      visibility: 'public',
    });
    expect(api.get).toHaveBeenCalledWith(
      expect.stringContaining('/catalog/providers/p1/remote-projects?'),
    );
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('page')).toBe('2');
    expect(params.get('per_page')).toBe('10');
    expect(params.get('search')).toBe('myrepo');
    expect(params.get('visibility')).toBe('public');
  });

  it('listRemoteProjects omits visibility when set to "all"', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.listRemoteProjects('p1', 1, 20, { visibility: 'all' });
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.has('visibility')).toBe(false);
  });

  it('listRemoteProjects includes sort and sortDir params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await providerService.listRemoteProjects('p1', 1, 20, { sort: 'name', sortDir: 'desc' });
    const url = vi.mocked(api.get).mock.calls[0][0] as string;
    const params = new URLSearchParams(url.split('?')[1]);
    expect(params.get('sort')).toBe('name');
    expect(params.get('sort_dir')).toBe('desc');
  });

  it('importProjects calls POST /catalog/providers/:id/import', async () => {
    const input = { projectIds: ['r1', 'r2'] };
    vi.mocked(api.post).mockResolvedValue({ data: [], status: 201 });
    await providerService.importProjects('p1', input as any);
    expect(api.post).toHaveBeenCalledWith('/catalog/providers/p1/import', input);
  });

  it('syncAll calls POST /catalog/providers/:id/sync-all without force', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'job1' }, status: 200 });
    await providerService.syncAll('p1');
    expect(api.post).toHaveBeenCalledWith('/catalog/providers/p1/sync-all', {});
  });

  it('syncAll calls POST with force=1 query param', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'job1' }, status: 200 });
    await providerService.syncAll('p1', true);
    expect(api.post).toHaveBeenCalledWith('/catalog/providers/p1/sync-all?force=1', {});
  });

  it('syncAll passes projectIds in body', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'job1' }, status: 200 });
    await providerService.syncAll('p1', false, ['proj1', 'proj2']);
    expect(api.post).toHaveBeenCalledWith('/catalog/providers/p1/sync-all', {
      projectIds: ['proj1', 'proj2'],
    });
  });

  it('syncAllGlobal calls POST /catalog/sync-all without force', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'job1' }, status: 200 });
    await providerService.syncAllGlobal();
    expect(api.post).toHaveBeenCalledWith('/catalog/sync-all', {});
  });

  it('syncAllGlobal calls POST /catalog/sync-all with force=1', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'job1' }, status: 200 });
    await providerService.syncAllGlobal(true);
    expect(api.post).toHaveBeenCalledWith('/catalog/sync-all?force=1', {});
  });

  it('getSyncJob calls GET /catalog/sync-jobs/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'job1', status: 'running' }, status: 200 });
    await providerService.getSyncJob('job1');
    expect(api.get).toHaveBeenCalledWith('/catalog/sync-jobs/job1');
  });
});
