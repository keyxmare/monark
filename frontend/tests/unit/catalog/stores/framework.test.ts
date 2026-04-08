import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/catalog/services/framework.service', () => ({
  frameworkService: {
    list: vi.fn(),
    remove: vi.fn(),
  },
}));

import { frameworkService } from '@/catalog/services/framework.service';
import { useFrameworkStore } from '@/catalog/stores/framework';

const mockFramework = {
  id: 'fw-1',
  projectId: 'p-1',
  name: 'Symfony',
  version: '7.2',
  latestLts: '7.1',
  ltsGap: '',
  maintenanceStatus: 'ok' as const,
};

describe('Framework Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches frameworks with paginated response', async () => {
    vi.mocked(frameworkService.list).mockResolvedValue({
      data: {
        items: [mockFramework],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useFrameworkStore();
    await store.fetchAll();

    expect(store.frameworks).toHaveLength(1);
    expect(store.total).toBe(1);
    expect(store.currentPage).toBe(1);
  });

  it('fetches frameworks with plain array response', async () => {
    vi.mocked(frameworkService.list).mockResolvedValue({
      data: [mockFramework],
      status: 200,
    });

    const store = useFrameworkStore();
    await store.fetchAll();

    expect(store.frameworks).toHaveLength(1);
    expect(store.total).toBe(1);
    expect(store.totalPages).toBe(1);
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(frameworkService.list).mockRejectedValue(new Error('Boom'));

    const store = useFrameworkStore();
    await store.fetchAll();

    expect(store.error).toBeTruthy();
    expect(store.loading).toBe(false);
  });

  it('removes a framework from the list', async () => {
    vi.mocked(frameworkService.remove).mockResolvedValue(undefined);

    const store = useFrameworkStore();
    store.frameworks = [mockFramework, { ...mockFramework, id: 'fw-2' }];

    await store.remove('fw-1');

    expect(store.frameworks).toHaveLength(1);
    expect(store.frameworks[0].id).toBe('fw-2');
  });

  it('sets error when remove fails', async () => {
    vi.mocked(frameworkService.remove).mockRejectedValue(new Error('Nope'));

    const store = useFrameworkStore();
    store.frameworks = [mockFramework];

    await store.remove('fw-1');

    expect(store.error).toBeTruthy();
    expect(store.frameworks).toHaveLength(1);
  });
});
