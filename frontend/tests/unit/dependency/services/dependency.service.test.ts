import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { dependencyService } from '@/dependency/services/dependency.service';

describe('dependencyService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /dependency/dependencies with default params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await dependencyService.list();
    expect(api.get).toHaveBeenCalledWith(
      '/dependency/dependencies?page=1&per_page=20',
    );
  });

  it('list calls GET with custom page and perPage', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await dependencyService.list(2, 50);
    expect(api.get).toHaveBeenCalledWith(
      '/dependency/dependencies?page=2&per_page=50',
    );
  });

  it('list calls GET with projectId filter', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await dependencyService.list(1, 20, 'proj-1');
    expect(api.get).toHaveBeenCalledWith(
      '/dependency/dependencies?page=1&per_page=20&project_id=proj-1',
    );
  });

  it('get calls GET /dependency/dependencies/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await dependencyService.get('dep-1');
    expect(api.get).toHaveBeenCalledWith('/dependency/dependencies/dep-1');
  });

  it('create calls POST /dependency/dependencies', async () => {
    const input = { name: 'lodash', version: '4.17.0' };
    vi.mocked(api.post).mockResolvedValue({ data: {}, status: 201 });
    await dependencyService.create(input as never);
    expect(api.post).toHaveBeenCalledWith('/dependency/dependencies', input);
  });

  it('update calls PUT /dependency/dependencies/:id', async () => {
    const input = { version: '4.18.0' };
    vi.mocked(api.put).mockResolvedValue({ data: {}, status: 200 });
    await dependencyService.update('dep-1', input as never);
    expect(api.put).toHaveBeenCalledWith('/dependency/dependencies/dep-1', input);
  });

  it('remove calls DELETE /dependency/dependencies/:id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);
    await dependencyService.remove('dep-1');
    expect(api.delete).toHaveBeenCalledWith('/dependency/dependencies/dep-1');
  });

  it('sync calls POST /dependency/sync', async () => {
    vi.mocked(api.post).mockResolvedValue({ data: { syncId: 's1' }, status: 200 });
    await dependencyService.sync();
    expect(api.post).toHaveBeenCalledWith('/dependency/sync', {});
  });

  it('stats calls GET /dependency/stats without params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await dependencyService.stats();
    expect(api.get).toHaveBeenCalledWith('/dependency/stats');
  });

  it('stats calls GET /dependency/stats with all params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await dependencyService.stats({
      projectId: 'p1',
      packageManager: 'npm',
      type: 'runtime',
    });
    expect(api.get).toHaveBeenCalledWith(
      '/dependency/stats?project_id=p1&package_manager=npm&type=runtime',
    );
  });

  it('stats calls GET /dependency/stats with partial params', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: {}, status: 200 });
    await dependencyService.stats({ packageManager: 'composer' });
    expect(api.get).toHaveBeenCalledWith(
      '/dependency/stats?package_manager=composer',
    );
  });
});
