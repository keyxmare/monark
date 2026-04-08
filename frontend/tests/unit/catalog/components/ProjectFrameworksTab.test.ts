import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div data-testid="pagination-stub" />' },
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/framework', () => ({
  useFrameworkStore: vi.fn(() => ({
    currentPage: 1,
    fetchAll: mockFetchAll,
    frameworks: [],
    loading: false,
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProjectFrameworksTab from '@/catalog/components/ProjectFrameworksTab.vue';

describe('ProjectFrameworksTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders the frameworks panel and calls fetchAll on mount', () => {
    const wrapper = mount(ProjectFrameworksTab, { props: { projectId: 'p-1' } });
    expect(wrapper.find('[data-testid="frameworks-panel"]').exists()).toBe(true);
    expect(mockFetchAll).toHaveBeenCalledWith(1, 20, 'p-1');
  });

  it('shows empty state when no frameworks', () => {
    const wrapper = mount(ProjectFrameworksTab, { props: { projectId: 'p-1' } });
    expect(wrapper.find('[data-testid="frameworks-empty"]').exists()).toBe(true);
  });

  it('renders rows for each framework with eol and warning badges', () => {
    storeOverrides = {
      frameworks: [
        {
          id: '1',
          projectId: 'p-1',
          name: 'Symfony',
          version: '5.4',
          latestLts: '6.4',
          ltsGap: '2 ans',
          maintenanceStatus: 'eol',
          eolDate: '2024-01-01',
        },
        {
          id: '2',
          projectId: 'p-1',
          name: 'React',
          version: '17',
          latestLts: '18',
          ltsGap: '1 an',
          maintenanceStatus: 'warning',
        },
        {
          id: '3',
          projectId: 'p-1',
          name: 'Vue',
          version: '3.5',
          latestLts: '3.5',
          ltsGap: '',
          maintenanceStatus: 'active',
        },
      ],
    };

    const wrapper = mount(ProjectFrameworksTab, { props: { projectId: 'p-1' } });
    const rows = wrapper.findAll('[data-testid="framework-row"]');
    expect(rows).toHaveLength(3);
    expect(wrapper.text()).toContain('catalog.techStacks.unmaintained');
    expect(wrapper.text()).toContain('catalog.techStacks.inactive');
  });

  it('does not show pagination when totalPages <= 1', () => {
    const wrapper = mount(ProjectFrameworksTab, { props: { projectId: 'p-1' } });
    expect(wrapper.find('[data-testid="pagination-stub"]').exists()).toBe(false);
  });
});
