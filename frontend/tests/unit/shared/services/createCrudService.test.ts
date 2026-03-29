import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/shared/utils/api', () => ({
  api: { delete: vi.fn(), get: vi.fn(), patch: vi.fn(), post: vi.fn(), put: vi.fn() },
}));

import { api } from '@/shared/utils/api';
import { createCrudService } from '@/shared/services/createCrudService';

interface TestEntity {
  id: string;
  name: string;
}

interface CreateTestEntity {
  name: string;
}

interface UpdateTestEntity {
  name?: string;
}

const service = createCrudService<TestEntity, CreateTestEntity, UpdateTestEntity>('/test/items');

describe('createCrudService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('list calls GET with page and perPage', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [], total: 0 }, status: 200 });
    await service.list(3, 15);
    expect(api.get).toHaveBeenCalledWith('/test/items?page=3&per_page=15');
  });

  it('list uses defaults (page=1, perPage=20) when no args', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { items: [], total: 0 }, status: 200 });
    await service.list();
    expect(api.get).toHaveBeenCalledWith('/test/items?page=1&per_page=20');
  });

  it('get calls GET with id', async () => {
    vi.mocked(api.get).mockResolvedValue({ data: { id: '42', name: 'Test' }, status: 200 });
    await service.get('42');
    expect(api.get).toHaveBeenCalledWith('/test/items/42');
  });

  it('create calls POST with data', async () => {
    const data: CreateTestEntity = { name: 'New Item' };
    vi.mocked(api.post).mockResolvedValue({ data: { id: '1', name: 'New Item' }, status: 201 });
    await service.create(data);
    expect(api.post).toHaveBeenCalledWith('/test/items', data);
  });

  it('update calls PUT with id and data', async () => {
    const data: UpdateTestEntity = { name: 'Updated' };
    vi.mocked(api.put).mockResolvedValue({ data: { id: '42', name: 'Updated' }, status: 200 });
    await service.update('42', data);
    expect(api.put).toHaveBeenCalledWith('/test/items/42', data);
  });

  it('remove calls DELETE with id', async () => {
    vi.mocked(api.delete).mockResolvedValue(undefined);
    await service.remove('42');
    expect(api.delete).toHaveBeenCalledWith('/test/items/42');
  });
});
