import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import type { CrudService } from '@/shared/types/crud';
import type { ApiResponse } from '@/shared/types';
import { createCrudStore } from '@/shared/stores/createCrudStore';

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

function createMockService(): CrudService<TestEntity, CreateTestEntity, UpdateTestEntity> {
  return {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  };
}

const mockItem: TestEntity = { id: '1', name: 'Item 1' };
const mockItem2: TestEntity = { id: '2', name: 'Item 2' };

describe('createCrudStore', () => {
  let mockService: CrudService<TestEntity, CreateTestEntity, UpdateTestEntity>;
  let useStore: ReturnType<
    typeof createCrudStore<TestEntity, CreateTestEntity, UpdateTestEntity>
  >;

  beforeEach(() => {
    setActivePinia(createPinia());
    mockService = createMockService();
    useStore = createCrudStore<TestEntity, CreateTestEntity, UpdateTestEntity>(
      'test-entity',
      mockService,
    );
  });

  it('starts with empty state', () => {
    const store = useStore();

    expect(store.items).toEqual([]);
    expect(store.current).toBeNull();
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
    expect(store.totalPages).toBe(0);
    expect(store.currentPage).toBe(0);
    expect(store.total).toBe(0);
  });

  it('fetchAll populates items and pagination', async () => {
    vi.mocked(mockService.list).mockResolvedValue({
      data: {
        items: [mockItem, mockItem2],
        total: 2,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    } as ApiResponse<any>);

    const store = useStore();
    await store.fetchAll();

    expect(store.items).toEqual([mockItem, mockItem2]);
    expect(store.totalPages).toBe(1);
    expect(store.currentPage).toBe(1);
    expect(store.total).toBe(2);
    expect(store.loading).toBe(false);
  });

  it('fetchAll sets error on failure', async () => {
    vi.mocked(mockService.list).mockRejectedValue(new Error('Network error'));

    const store = useStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load items');
    expect(store.loading).toBe(false);
  });

  it('fetchOne populates current', async () => {
    vi.mocked(mockService.get).mockResolvedValue({
      data: mockItem,
      status: 200,
    });

    const store = useStore();
    await store.fetchOne('1');

    expect(store.current).toEqual(mockItem);
    expect(store.loading).toBe(false);
  });

  it('create returns item and prepends to list', async () => {
    vi.mocked(mockService.create).mockResolvedValue({
      data: mockItem,
      status: 201,
    });

    const store = useStore();
    store.items = [mockItem2];

    const result = await store.create({ name: 'Item 1' });

    expect(result).toEqual(mockItem);
    expect(store.items).toHaveLength(2);
    expect(store.items[0]).toEqual(mockItem);
    expect(store.loading).toBe(false);
  });

  it('create throws and sets error on failure', async () => {
    vi.mocked(mockService.create).mockRejectedValue(new Error('Server error'));

    const store = useStore();

    await expect(store.create({ name: 'fail' })).rejects.toThrow('Failed to create items');
    expect(store.error).toBe('Failed to create items');
    expect(store.loading).toBe(false);
  });

  it('update returns updated item and updates in list', async () => {
    const updated = { id: '1', name: 'Updated' };
    vi.mocked(mockService.update).mockResolvedValue({
      data: updated,
      status: 200,
    });

    const store = useStore();
    store.items = [mockItem, mockItem2];

    const result = await store.update('1', { name: 'Updated' });

    expect(result).toEqual(updated);
    expect(store.current).toEqual(updated);
    expect(store.items[0]).toEqual(updated);
    expect(store.loading).toBe(false);
  });

  it('remove removes item from list', async () => {
    vi.mocked(mockService.remove).mockResolvedValue(undefined);

    const store = useStore();
    store.items = [mockItem, mockItem2];

    await store.remove('1');

    expect(store.items).toHaveLength(1);
    expect(store.items[0]).toEqual(mockItem2);
    expect(store.loading).toBe(false);
  });
});
