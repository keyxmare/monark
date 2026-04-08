import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useDependencyStore } from '@/dependency/stores/dependency';

vi.mock('@/dependency/services/dependency.service', () => ({
  dependencyService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}));

import { dependencyService } from '@/dependency/services/dependency.service';

const mockDependency = {
  id: 'dep-001',
  name: 'symfony/framework-bundle',
  currentVersion: '7.2.0',
  latestVersion: '8.0.0',
  ltsVersion: '7.4.0',
  packageManager: 'composer' as const,
  type: 'runtime' as const,
  isOutdated: true,
  projectId: '00000000-0000-7000-8000-000000000001',
  repositoryUrl: 'https://github.com/symfony/symfony',
  vulnerabilityCount: 2,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
};

describe('Dependency Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('fetches all dependencies', async () => {
    vi.mocked(dependencyService.list).mockResolvedValue({
      data: {
        items: [mockDependency],
        total: 1,
        page: 1,
        per_page: 20,
        total_pages: 1,
      },
      status: 200,
    });

    const store = useDependencyStore();
    await store.fetchAll();

    expect(store.dependencies).toHaveLength(1);
    expect(store.dependencies[0].name).toBe('symfony/framework-bundle');
  });

  it('fetches a single dependency', async () => {
    vi.mocked(dependencyService.get).mockResolvedValue({
      data: mockDependency,
      status: 200,
    });

    const store = useDependencyStore();
    await store.fetchOne('dep-001');

    expect(store.selectedDependency).toEqual(mockDependency);
  });

  it('creates a dependency', async () => {
    vi.mocked(dependencyService.create).mockResolvedValue({
      data: mockDependency,
      status: 201,
    });

    const store = useDependencyStore();
    const result = await store.create({
      name: 'symfony/framework-bundle',
      currentVersion: '7.2.0',
      latestVersion: '8.0.0',
      ltsVersion: '7.4.0',
      packageManager: 'composer',
      type: 'runtime',
      isOutdated: true,
      projectId: '00000000-0000-7000-8000-000000000001',
    });

    expect(result.id).toBe('dep-001');
    expect(store.dependencies).toHaveLength(1);
  });

  it('updates a dependency', async () => {
    vi.mocked(dependencyService.create).mockResolvedValue({
      data: mockDependency,
      status: 201,
    });
    const updatedDep = { ...mockDependency, currentVersion: '8.0.0', isOutdated: false };
    vi.mocked(dependencyService.update).mockResolvedValue({
      data: updatedDep,
      status: 200,
    });

    const store = useDependencyStore();
    await store.create(mockDependency as never);
    await store.update('dep-001', { currentVersion: '8.0.0', isOutdated: false });

    expect(store.selectedDependency?.currentVersion).toBe('8.0.0');
  });

  it('removes a dependency', async () => {
    vi.mocked(dependencyService.create).mockResolvedValue({
      data: mockDependency,
      status: 201,
    });
    vi.mocked(dependencyService.remove).mockResolvedValue(undefined);

    const store = useDependencyStore();
    await store.create(mockDependency as never);

    await store.remove('dep-001');

    expect(store.dependencies).toHaveLength(0);
  });

  it('sets error on fetch failure', async () => {
    vi.mocked(dependencyService.list).mockRejectedValue(new Error('Network error'));

    const store = useDependencyStore();
    await store.fetchAll();

    expect(store.error).toBe('Failed to load dependencies');
  });

  it('exposes repositoryUrl from fetched dependency', async () => {
    vi.mocked(dependencyService.get).mockResolvedValue({
      data: mockDependency,
      status: 200,
    });

    const store = useDependencyStore();
    await store.fetchOne('dep-001');

    expect(store.selectedDependency?.repositoryUrl).toBe('https://github.com/symfony/symfony');
  });

  it('handles null repositoryUrl', async () => {
    const depWithoutUrl = { ...mockDependency, repositoryUrl: null };
    vi.mocked(dependencyService.get).mockResolvedValue({
      data: depWithoutUrl,
      status: 200,
    });

    const store = useDependencyStore();
    await store.fetchOne('dep-001');

    expect(store.selectedDependency?.repositoryUrl).toBeNull();
  });
});
