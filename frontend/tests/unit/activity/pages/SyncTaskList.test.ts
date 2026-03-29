import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ d: (v: Date) => v.toISOString(), t: (key: string) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockFetchAll = vi.fn();
const mockFetchStats = vi.fn();
const mockUpdateStatus = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/sync-task', () => ({
  useSyncTaskStore: vi.fn(() => ({
    tasks: [],
    stats: null,
    error: null,
    fetchAll: mockFetchAll,
    fetchStats: mockFetchStats,
    updateStatus: mockUpdateStatus,
    loading: false,
    ...storeOverrides,
  })),
}));

import SyncTaskList from '@/activity/pages/SyncTaskList.vue';

describe('SyncTaskList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll and fetchStats on mount', () => {
    mount(SyncTaskList);
    expect(mockFetchAll).toHaveBeenCalled();
    expect(mockFetchStats).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Server error' };
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-error"]').exists()).toBe(true);
  });

  it('shows empty state when no tasks', () => {
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-empty"]').exists()).toBe(true);
  });

  it('renders task rows when tasks exist', () => {
    storeOverrides = {
      tasks: [
        {
          id: 'task-1',
          type: 'outdated_dependency',
          severity: 'medium',
          title: 'Outdated dep',
          description: 'desc',
          status: 'open',
          metadata: {},
          projectId: 'p1',
          resolvedAt: null,
          createdAt: '2025-01-01T00:00:00+00:00',
          updatedAt: '2025-01-01T00:00:00+00:00',
        },
      ],
    };
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-row-task-1"]').exists()).toBe(true);
  });

  it('renders stats when available', () => {
    storeOverrides = {
      stats: {
        bySeverity: [
          { label: 'critical', count: 2 },
          { label: 'high', count: 5 },
        ],
      },
    };
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-stats"]').exists()).toBe(true);
  });

  it('renders filter dropdowns', () => {
    const wrapper = mount(SyncTaskList);
    expect(wrapper.find('[data-testid="sync-task-filters"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="filter-status"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="filter-type"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="filter-severity"]').exists()).toBe(true);
  });
});
