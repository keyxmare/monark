import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/ExportDropdown.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/dependency/components/DependencyFilters.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/dependency/components/DependencyHealthScore.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/composables/useFrameworkLts', () => ({
  humanizeMs: vi.fn(() => ''),
  humanizeTimeDiff: vi.fn(() => ''),
  ltsUrgency: vi.fn(() => 'fresh'),
}));

vi.mock('@/dependency/composables/useDependencySyncProgress', () => ({
  useDependencySyncProgress: () => ({
    track: vi.fn(),
  }),
}));

vi.mock('@/dependency/services/dependency.service', () => ({
  dependencyService: {
    stats: vi
      .fn()
      .mockResolvedValue({ data: { outdated: 0, total: 0, upToDate: 0, totalVulnerabilities: 0 } }),
    sync: vi.fn().mockResolvedValue({ data: { syncId: 'abc' } }),
  },
}));

vi.mock('@/dependency/services/dependencyPdfExport', () => ({
  exportDependenciesPdf: vi.fn(),
}));

vi.mock('@/shared/stores/toast', () => ({
  useToastStore: vi.fn(() => ({
    addToast: vi.fn(),
    toasts: [],
  })),
}));

const mockDepFetchAll = vi.fn();
let depStoreOverrides: Record<string, unknown> = {};

vi.mock('@/dependency/stores/dependency', () => ({
  useDependencyStore: vi.fn(() => ({
    currentPage: 1,
    dependencies: [],
    error: null,
    fetchAll: mockDepFetchAll,
    loading: false,
    remove: vi.fn(),
    total: 0,
    totalPages: 0,
    ...depStoreOverrides,
  })),
}));

const mockProjectFetchAll = vi.fn();
vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockProjectFetchAll,
    loading: false,
    projects: [],
    total: 0,
    totalPages: 0,
  })),
}));

import DependencyList from '@/dependency/pages/DependencyList.vue';

describe('DependencyList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    depStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(DependencyList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-list-page"]').exists()).toBe(true);
  });

  it('shows loading state', () => {
    depStoreOverrides = { loading: true };
    const wrapper = mount(DependencyList);
    expect(wrapper.find('[data-testid="dependency-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    depStoreOverrides = { error: 'Failed to fetch' };
    const wrapper = mount(DependencyList);
    expect(wrapper.find('[data-testid="dependency-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-list-error"]').text()).toContain(
      'Failed to fetch',
    );
  });

  it('shows empty state when no dependencies', () => {
    const wrapper = mount(DependencyList);
    expect(wrapper.find('[data-testid="dependency-list-empty"]').exists()).toBe(true);
  });

  it('renders dependency rows when dependencies exist', () => {
    depStoreOverrides = {
      dependencies: [
        {
          id: '1',
          name: 'lodash',
          projectId: 'p1',
          currentVersion: '4.17.0',
          latestVersion: '4.17.21',
          packageManager: 'npm',
          type: 'runtime',
          isOutdated: true,
          vulnerabilityCount: 0,
          registryStatus: 'found',
          currentVersionReleasedAt: null,
          latestVersionReleasedAt: null,
        },
      ],
    };
    const wrapper = mount(DependencyList);
    const rows = wrapper.findAll('[data-testid="dependency-list-row"]');
    expect(rows.length).toBeGreaterThanOrEqual(1);
  });
});
