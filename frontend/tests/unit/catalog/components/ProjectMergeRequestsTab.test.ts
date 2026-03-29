import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMergeRequest } from '../../../factories';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string) => key,
  }),
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/merge-request', () => ({
  useMergeRequestStore: vi.fn(() => ({
    currentPage: 1,
    fetchAll: mockFetchAll,
    loading: false,
    mergeRequests: [],
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProjectMergeRequestsTab from '@/catalog/components/ProjectMergeRequestsTab.vue';

describe('ProjectMergeRequestsTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders the merge requests panel', () => {
    const wrapper = mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="merge-requests-panel"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    expect(mockFetchAll).toHaveBeenCalledWith('project-1', 1, 20, 'active');
  });

  it('renders filter controls', () => {
    const wrapper = mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="merge-requests-filters"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-status"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-search-author"]').exists()).toBe(true);
  });

  it('shows empty state when no merge requests', () => {
    const wrapper = mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="merge-requests-empty"]').exists()).toBe(true);
  });

  it('renders merge request rows when data exists', () => {
    storeOverrides = {
      mergeRequests: [
        createMergeRequest({ id: 'mr-1', title: 'Fix auth bug' }),
        createMergeRequest({ id: 'mr-2', title: 'Add tests' }),
      ],
    };
    const wrapper = mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    const rows = wrapper.findAll('[data-testid="mr-row"]');
    expect(rows).toHaveLength(2);
  });

  it('filters merge requests by status', async () => {
    storeOverrides = {
      mergeRequests: [
        createMergeRequest({ id: 'mr-1', status: 'open', title: 'Open MR' }),
        createMergeRequest({ id: 'mr-2', status: 'merged', title: 'Merged MR' }),
      ],
    };
    const wrapper = mount(ProjectMergeRequestsTab, { props: { projectId: 'project-1' } });
    await wrapper.find('[data-testid="mr-filter-status"]').setValue('open');
    const rows = wrapper.findAll('[data-testid="mr-row"]');
    expect(rows).toHaveLength(1);
    expect(rows[0].text()).toContain('Open MR');
  });
});
