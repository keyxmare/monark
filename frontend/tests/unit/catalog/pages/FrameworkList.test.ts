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
  default: { template: '<div />', emits: ['export'] },
}));

vi.mock('@/shared/components/TechBadge.vue', () => ({
  default: { props: ['name', 'version', 'size'], template: '<span />' },
}));

vi.mock('@/catalog/components/ProviderIcon.vue', () => ({
  default: { props: ['type', 'size'], template: '<span />' },
}));

vi.mock('@/shared/utils/dateFormat', () => ({
  formatRelative: vi.fn(() => '2 days ago'),
}));

const mockFrameworkFetchAll = vi.fn();
let frameworkStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/framework', () => ({
  useFrameworkStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockFrameworkFetchAll,
    frameworks: [],
    loading: false,
    total: 0,
    totalPages: 0,
    ...frameworkStoreOverrides,
  })),
}));

const mockProjectFetchAll = vi.fn();
vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    fetchAll: mockProjectFetchAll,
    projects: [],
  })),
}));

const mockProviderFetchAll = vi.fn();
vi.mock('@/catalog/stores/provider', () => ({
  useProviderStore: vi.fn(() => ({
    fetchAll: mockProviderFetchAll,
    providers: [],
  })),
}));

vi.mock('@/shared/composables/useGlobalSync', () => ({
  useGlobalSync: () => ({
    currentSync: { value: null },
    isRunning: { value: false },
    startSync: vi.fn(),
    loadCurrent: vi.fn(),
    onStepCompleted: vi.fn(),
  }),
}));

vi.mock('@/shared/components/SyncButton.vue', () => ({
  default: { template: '<button data-testid="sync-button" />' },
}));

vi.mock('@/catalog/composables/useFrameworkGrouping', () => ({
  useFrameworkGrouping: () => ({
    availableFrameworks: [],
    availableProviders: [],
    filterFramework: { value: '' },
    filterProvider: { value: '' },
    filterStatus: { value: '' },
    filteredFrameworks: { value: [] },
    groupBy: { value: 'project' },
    groupedFrameworks: [],
    healthScore: { value: null },
    providerAggregates: { value: [] },
    search: { value: '' },
    sortIndicator: vi.fn(() => ''),
    toggleSort: vi.fn(),
  }),
}));

import FrameworkList from '@/catalog/pages/FrameworkList.vue';

describe('FrameworkList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    frameworkStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="framework-list-page"]').exists()).toBe(true);
  });

  it('renders breadcrumb', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-list-breadcrumb"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(FrameworkList);
    expect(mockFrameworkFetchAll).toHaveBeenCalled();
    expect(mockProjectFetchAll).toHaveBeenCalled();
    expect(mockProviderFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    frameworkStoreOverrides = { loading: true };
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    frameworkStoreOverrides = { error: 'Network error' };
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="framework-list-error"]').text()).toContain('Network error');
  });

  it('shows empty state when no frameworks', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-list-empty"]').exists()).toBe(true);
  });

  it('shows empty providers link', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-empty-providers-link"]').exists()).toBe(true);
  });

  it('renders sync button', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="sync-button"]').exists()).toBe(true);
  });

  it('shows filters when not loading', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-filters"]').exists()).toBe(true);
  });

  it('renders group toggle buttons', () => {
    const wrapper = mount(FrameworkList);
    expect(wrapper.find('[data-testid="framework-group-toggle"]').exists()).toBe(true);
  });
});
