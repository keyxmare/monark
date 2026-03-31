import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string, params?: Record<string, string>) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

vi.mock('@/shared/components/ConfirmDialog.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/DropdownMenu.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/TechBadge.vue', () => ({
  default: { props: ['name', 'size'], template: '<span>{{ name }}</span>' },
}));

vi.mock('@/shared/composables/useConfirmDelete', () => ({
  useConfirmDelete: () => ({
    cancel: vi.fn(),
    confirm: vi.fn(),
    isOpen: false,
    requestDelete: vi.fn(),
    target: null,
  }),
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

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    currentPage: 1,
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    projects: [],
    remove: vi.fn(),
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProjectList from '@/catalog/pages/ProjectList.vue';

describe('ProjectList', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProjectList);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-list-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ProjectList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('[data-testid="project-list-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    storeOverrides = { error: 'Network error' };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('[data-testid="project-list-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-list-error"]').text()).toContain('Network error');
  });

  it('shows empty state when no projects', () => {
    const wrapper = mount(ProjectList);
    expect(wrapper.find('[data-testid="project-list-empty"]').exists()).toBe(true);
  });

  it('renders project cards when projects exist', () => {
    storeOverrides = {
      projects: [
        {
          id: '1',
          name: 'Project A',
          repositoryUrl: 'https://git.example.com/a',
          visibility: 'public',
          defaultBranch: 'main',
          techStacks: [],
        },
        {
          id: '2',
          name: 'Project B',
          repositoryUrl: 'https://git.example.com/b',
          visibility: 'private',
          defaultBranch: 'develop',
          techStacks: [],
        },
      ],
    };
    const wrapper = mount(ProjectList);
    const cards = wrapper.findAll('[data-testid="project-list-card"]');
    expect(cards).toHaveLength(2);
  });

  it('shows filters when projects exist', () => {
    storeOverrides = {
      projects: [
        {
          id: '1',
          name: 'P',
          repositoryUrl: '',
          visibility: 'public',
          defaultBranch: 'main',
          techStacks: [],
        },
      ],
    };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('[data-testid="project-list-filters"]').exists()).toBe(true);
  });
});
