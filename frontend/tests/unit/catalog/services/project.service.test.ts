import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { projectService } from '@/catalog/services/project.service';

describe('projectService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /catalog/projects with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [], total: 0 }, status: 200 });
    await projectService.list();
    expect(api.get).toHaveBeenCalledWith('/catalog/projects?page=1&per_page=20');
  });

  it('list calls GET /catalog/projects with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [], total: 0 }, status: 200 });
    await projectService.list(2, 10);
    expect(api.get).toHaveBeenCalledWith('/catalog/projects?page=2&per_page=10');
  });

  it('get calls GET /catalog/projects/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'abc' }, status: 200 });
    await projectService.get('abc');
    expect(api.get).toHaveBeenCalledWith('/catalog/projects/abc');
  });

  it('create calls POST /catalog/projects', async () => {
    const input = { name: 'New Project', repositoryUrl: 'https://example.com' };
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'new' }, status: 201 });
    await projectService.create(input as any);
    expect(api.post).toHaveBeenCalledWith('/catalog/projects', input);
  });

  it('update calls PUT /catalog/projects/:id', async () => {
    const input = { name: 'Updated' };
    vi.mocked(api.put).mockResolvedValue({ data: { id: 'abc' }, status: 200 });
    await projectService.update('abc', input as any);
    expect(api.put).toHaveBeenCalledWith('/catalog/projects/abc', input);
  });

  it('remove calls DELETE /catalog/projects/:id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);
    await projectService.remove('abc');
    expect(api.delete).toHaveBeenCalledWith('/catalog/projects/abc');
  });

  it('scan calls POST /catalog/projects/:id/scan', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { status: 'ok' }, status: 200 });
    await projectService.scan('abc');
    expect(api.post).toHaveBeenCalledWith('/catalog/projects/abc/scan', {});
  });
});
