import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { projectId: 'project-1' }, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({
    d: (date: Date) => date.toISOString(),
    t: (key: string, params?: Record<string, string>) => key,
  }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/merge-request', () => ({
  useMergeRequestStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    mergeRequests: [],
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import MergeRequestList from '@/catalog/pages/MergeRequestList.vue';

function createMergeRequest(overrides: Record<string, unknown> = {}) {
  return {
    id: 'mr-1',
    externalId: '101',
    title: 'feat: add user auth',
    description: 'Implements user authentication flow',
    sourceBranch: 'feature/auth',
    targetBranch: 'main',
    status: 'open',
    author: 'johndoe',
    url: 'https://github.com/acme/my-project/pull/101',
    additions: 150,
    deletions: 30,
    reviewers: [],
    labels: [],
    mergedAt: null,
    closedAt: null,
    projectId: 'project-1',
    createdAt: '2025-01-01T00:00:00+00:00',
    updatedAt: '2025-01-01T00:00:00+00:00',
    ...overrides,
  };
}

describe('MergeRequestList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(MergeRequestList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="merge-request-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(MergeRequestList);
    expect(mockFetchAll).toHaveBeenCalledWith('project-1', 1, 20, 'active', undefined);
  });

  it('renders status filter buttons', () => {
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-status-filter"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-active"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-open"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-draft"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-merged"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-closed"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-filter-all"]').exists()).toBe(true);
  });

  it('renders author filter input', () => {
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-author-filter"]').exists()).toBe(true);
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Network error' };
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="mr-error"]').text()).toContain('Network error');
  });

  it('shows empty state when no merge requests', () => {
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-empty"]').exists()).toBe(true);
  });

  it('renders merge request rows when data exists', () => {
    storeOverrides = {
      mergeRequests: [
        createMergeRequest({ id: 'mr-1', title: 'feat: auth' }),
        createMergeRequest({ id: 'mr-2', title: 'fix: typo' }),
      ],
    };
    const wrapper = mount(MergeRequestList);
    const rows = wrapper.findAll('[data-testid="mr-row"]');
    expect(rows).toHaveLength(2);
  });

  it('renders status badges on merge request rows', () => {
    storeOverrides = {
      mergeRequests: [createMergeRequest({ id: 'mr-1', status: 'open' })],
    };
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-status-badge"]').exists()).toBe(true);
  });

  it('renders external links on merge request rows', () => {
    storeOverrides = {
      mergeRequests: [
        createMergeRequest({ id: 'mr-1', url: 'https://github.com/test/pull/1' }),
      ],
    };
    const wrapper = mount(MergeRequestList);
    expect(wrapper.find('[data-testid="mr-external-link"]').exists()).toBe(true);
  });
});
