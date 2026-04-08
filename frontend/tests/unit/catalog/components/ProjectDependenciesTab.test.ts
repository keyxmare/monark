import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createDependency } from '../../../factories';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/dependency/stores/dependency', () => ({
  useDependencyStore: vi.fn(() => ({
    currentPage: 1,
    dependencies: [],
    fetchAll: mockFetchAll,
    loading: false,
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProjectDependenciesTab from '@/catalog/components/ProjectDependenciesTab.vue';

describe('ProjectDependenciesTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders the dependencies panel', () => {
    const wrapper = mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="dependencies-panel"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    expect(mockFetchAll).toHaveBeenCalledWith(1, 20, 'project-1');
  });

  it('renders filter controls', () => {
    const wrapper = mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="dependencies-filters"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependencies-search"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependencies-filter-pm"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependencies-filter-type"]').exists()).toBe(true);
  });

  it('shows empty state when no dependencies', () => {
    const wrapper = mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="dependencies-empty"]').exists()).toBe(true);
  });

  it('renders dependency rows when data exists', () => {
    storeOverrides = {
      dependencies: [
        createDependency({ id: 'dep-1', name: 'vue' }),
        createDependency({ id: 'dep-2', name: 'lodash' }),
      ],
    };
    const wrapper = mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    const rows = wrapper.findAll('[data-testid="dependency-row"]');
    expect(rows).toHaveLength(2);
  });

  it('filters dependencies by search', async () => {
    storeOverrides = {
      dependencies: [
        createDependency({ id: 'dep-1', name: 'vue' }),
        createDependency({ id: 'dep-2', name: 'lodash' }),
      ],
    };
    const wrapper = mount(ProjectDependenciesTab, { props: { projectId: 'project-1' } });
    await wrapper.find('[data-testid="dependencies-search"]').setValue('vue');
    const rows = wrapper.findAll('[data-testid="dependency-row"]');
    expect(rows).toHaveLength(1);
    expect(rows[0].text()).toContain('vue');
  });
});
