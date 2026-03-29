import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { techStackService } from '@/catalog/services/tech-stack.service';

describe('techStackService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET /catalog/tech-stacks with default pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await techStackService.list();
    expect(api.get).toHaveBeenCalledWith('/catalog/tech-stacks?page=1&per_page=20');
  });

  it('list calls GET with custom pagination', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await techStackService.list(2, 10);
    expect(api.get).toHaveBeenCalledWith('/catalog/tech-stacks?page=2&per_page=10');
  });

  it('list appends project_id when provided', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [] }, status: 200 });
    await techStackService.list(1, 20, 'proj-123');
    expect(api.get).toHaveBeenCalledWith(
      '/catalog/tech-stacks?page=1&per_page=20&project_id=proj-123',
    );
  });

  it('get calls GET /catalog/tech-stacks/:id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: 'ts1' }, status: 200 });
    await techStackService.get('ts1');
    expect(api.get).toHaveBeenCalledWith('/catalog/tech-stacks/ts1');
  });

  it('create calls POST /catalog/tech-stacks', async () => {
    const input = { language: 'PHP', framework: 'Symfony', projectId: 'proj-1' };
    vi.mocked(api.post).mockResolvedValue({ data: { id: 'ts-new' }, status: 201 });
    await techStackService.create(input as any);
    expect(api.post).toHaveBeenCalledWith('/catalog/tech-stacks', input);
  });

  it('remove calls DELETE /catalog/tech-stacks/:id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);
    await techStackService.remove('ts1');
    expect(api.delete).toHaveBeenCalledWith('/catalog/tech-stacks/ts1');
  });
});
