import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string) => key,
  }),
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

vi.mock('@/catalog/components/ProviderIcon.vue', () => ({
  default: { props: ['type', 'size'], template: '<span />' },
}));

vi.mock('@/catalog/components/TechStackFilters.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/components/TechStackTable.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/services/techStackPdfExport', () => ({
  exportTechStacksPdf: vi.fn(),
}));

const mockTechStackFetchAll = vi.fn();
let techStackStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/tech-stack', () => ({
  useTechStackStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockTechStackFetchAll,
    loading: false,
    techStacks: [],
    total: 0,
    totalPages: 0,
    ...techStackStoreOverrides,
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
const mockSyncAllGlobal = vi.fn();
vi.mock('@/catalog/stores/provider', () => ({
  useProviderStore: vi.fn(() => ({
    fetchAll: mockProviderFetchAll,
    providers: [],
    syncAllGlobal: mockSyncAllGlobal,
  })),
}));

vi.mock('@/catalog/composables/useSyncProgress', () => ({
  useSyncProgress: () => ({
    track: vi.fn(),
  }),
}));

vi.mock('@/catalog/composables/useTechStackGrouping', () => ({
  useTechStackGrouping: () => ({
    availableFrameworks: [],
    availableProviders: [],
    filterFramework: '',
    filterProvider: '',
    filterStatus: '',
    filteredStacks: { value: [] },
    groupBy: 'none',
    groupedStacks: { value: new Map() },
    healthScore: { value: null },
    providerAggregates: { value: [] },
    search: '',
    sortIndicator: vi.fn(() => ''),
    toggleSort: vi.fn(),
  }),
}));

import TechStackList from '@/catalog/pages/TechStackList.vue';

describe('TechStackList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    techStackStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(TechStackList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-list-page"]').exists()).toBe(true);
  });

  it('renders breadcrumb', () => {
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-list-breadcrumb"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(TechStackList);
    expect(mockTechStackFetchAll).toHaveBeenCalled();
    expect(mockProjectFetchAll).toHaveBeenCalled();
    expect(mockProviderFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    techStackStoreOverrides = { loading: true };
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    techStackStoreOverrides = { error: 'Network error' };
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tech-stack-list-error"]').text()).toContain('Network error');
  });

  it('shows empty state when no tech stacks', () => {
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-list-empty"]').exists()).toBe(true);
  });

  it('shows empty providers link', () => {
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-empty-providers-link"]').exists()).toBe(true);
  });

  it('renders sync all button', () => {
    const wrapper = mount(TechStackList);
    expect(wrapper.find('[data-testid="tech-stack-sync-all"]').exists()).toBe(true);
  });

  it('calls syncAllGlobal when sync button clicked', async () => {
    mockSyncAllGlobal.mockResolvedValueOnce({ id: 'sync-1', projectsCount: 3 });
    const wrapper = mount(TechStackList);
    await wrapper.find('[data-testid="tech-stack-sync-all"]').trigger('click');
    await vi.dynamicImportSettled();

    expect(mockSyncAllGlobal).toHaveBeenCalled();
  });
});
