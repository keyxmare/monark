import { setActivePinia, createPinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { dependencyService } from '@/dependency/services/dependency.service';
import { useDependencyStore } from '../dependency';

vi.mock('@/dependency/services/dependency.service');

const mockPaginatedResponse = (items: unknown[]) => ({
  data: { items, total_pages: 1, page: 1, total: items.length, per_page: items.length },
});

describe('useDependencyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('exposes dependencies alias pointing to same ref as items', async () => {
    vi.mocked(dependencyService.list).mockResolvedValue(
      mockPaginatedResponse([{ id: '1', name: 'lodash' }]) as never,
    );
    const store = useDependencyStore();
    await store.fetchAll();
    expect(store.dependencies).toHaveLength(1);
    expect(store.items).toBe(store.dependencies);
  });

  it('exposes selectedDependency alias pointing to same ref as current', () => {
    const store = useDependencyStore();
    expect(store.selectedDependency).toBe(store.current);
  });

  it('passes projectId to service.list when provided', async () => {
    vi.mocked(dependencyService.list).mockResolvedValue(mockPaginatedResponse([]) as never);
    const store = useDependencyStore();
    await store.fetchAll(1, 20, 'project-42');
    expect(dependencyService.list).toHaveBeenCalledWith(1, 20, 'project-42');
  });

  it('passes undefined projectId when not provided', async () => {
    vi.mocked(dependencyService.list).mockResolvedValue(mockPaginatedResponse([]) as never);
    const store = useDependencyStore();
    await store.fetchAll(1, 20);
    expect(dependencyService.list).toHaveBeenCalledWith(1, 20, undefined);
  });

  it('sets loading to true during fetch then false after', async () => {
    let resolveList!: (v: unknown) => void;
    vi.mocked(dependencyService.list).mockReturnValue(
      new Promise((res) => {
        resolveList = res;
      }) as never,
    );
    const store = useDependencyStore();
    const fetchPromise = store.fetchAll();
    expect(store.loading).toBe(true);
    resolveList(mockPaginatedResponse([]));
    await fetchPromise;
    expect(store.loading).toBe(false);
  });
});
