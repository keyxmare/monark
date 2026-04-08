import { describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { frameworkService } from '@/catalog/services/framework.service';
import { api } from '@/shared/utils/api';

describe('frameworkService', () => {
  it('list builds a paginated URL', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { items: [] } } as never);
    await frameworkService.list(3, 50);
    expect(api.get).toHaveBeenCalledWith('/catalog/frameworks?page=3&per_page=50');
  });

  it('list appends project_id when provided', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { items: [] } } as never);
    await frameworkService.list(1, 20, 'proj-42');
    expect(api.get).toHaveBeenCalledWith(
      '/catalog/frameworks?page=1&per_page=20&project_id=proj-42',
    );
  });

  it('get uses crud fetch by id', async () => {
    vi.mocked(api.get).mockResolvedValueOnce({ data: { id: 'fw-1' } } as never);
    const res = await frameworkService.get('fw-1');
    expect(api.get).toHaveBeenCalledWith('/catalog/frameworks/fw-1');
    expect(res.data).toEqual({ id: 'fw-1' });
  });

  it('remove deletes by id', async () => {
    vi.mocked(api.delete).mockResolvedValueOnce(undefined as never);
    await frameworkService.remove('fw-1');
    expect(api.delete).toHaveBeenCalledWith('/catalog/frameworks/fw-1');
  });
});
