import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'project-1' }, query: {} })),
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

vi.mock('@/shared/components/ConfirmDialog.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/shared/components/TechBadge.vue', () => ({
  default: { props: ['name', 'version', 'size'], template: '<span>{{ name }}</span>' },
}));

vi.mock('@/catalog/components/ProjectTechStacksTab.vue', () => ({
  default: { props: ['projectId'], template: '<div data-testid="tech-stacks-tab" />' },
}));

vi.mock('@/catalog/components/ProjectDependenciesTab.vue', () => ({
  default: { props: ['projectId'], template: '<div data-testid="dependencies-tab" />' },
}));

vi.mock('@/catalog/components/ProjectMergeRequestsTab.vue', () => ({
  default: { props: ['projectId'], template: '<div data-testid="merge-requests-tab" />' },
}));

const mockProjectFetchOne = vi.fn();
const mockProjectScan = vi.fn();
const mockProjectRemove = vi.fn();
let projectStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    error: null,
    fetchOne: mockProjectFetchOne,
    loading: false,
    remove: mockProjectRemove,
    scan: mockProjectScan,
    scanResult: null,
    scanning: false,
    selected: null,
    ...projectStoreOverrides,
  })),
}));

let techStackStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/tech-stack', () => ({
  useTechStackStore: vi.fn(() => ({
    fetchAll: vi.fn(),
    techStacks: [],
    total: 0,
    ...techStackStoreOverrides,
  })),
}));

vi.mock('@/dependency/stores/dependency', () => ({
  useDependencyStore: vi.fn(() => ({
    fetchAll: vi.fn(),
    total: 0,
  })),
}));

vi.mock('@/catalog/stores/merge-request', () => ({
  useMergeRequestStore: vi.fn(() => ({
    total: 0,
  })),
}));

vi.mock('@/shared/stores/toast', () => ({
  useToastStore: vi.fn(() => ({
    addToast: vi.fn(),
  })),
}));

import ProjectDetail from '@/catalog/pages/ProjectDetail.vue';

describe('ProjectDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    projectStoreOverrides = {};
    techStackStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(ProjectDetail);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-detail-page"]').exists()).toBe(true);
  });

  it('renders breadcrumb', () => {
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-detail-breadcrumb"]').exists()).toBe(true);
  });

  it('calls fetchOne on mount', () => {
    mount(ProjectDetail);
    expect(mockProjectFetchOne).toHaveBeenCalledWith('project-1');
  });

  it('shows loading state', () => {
    projectStoreOverrides = { loading: true };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    projectStoreOverrides = { error: 'Not found' };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-detail-error"]').exists()).toBe(true);
  });

  it('renders project info when selected', () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: 'A test project',
        externalId: 'ext-1',
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-detail-description"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-detail-repository-url"]').exists()).toBe(true);
  });

  it('renders stat cards when selected', () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: null,
        externalId: 'ext-1',
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-stats-cards"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-stat-visibility"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="project-stat-freshness"]').exists()).toBe(true);
  });

  it('renders scan button when project has externalId', () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: null,
        externalId: 'ext-1',
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-scan-btn"]').exists()).toBe(true);
  });

  it('renders unfollow button', () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: null,
        externalId: null,
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="project-unfollow-btn"]').exists()).toBe(true);
  });

  it('renders tab buttons', () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: null,
        externalId: null,
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);
    expect(wrapper.find('[data-testid="tab-tech-stacks"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tab-dependencies"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="tab-merge-requests"]').exists()).toBe(true);
  });

  it('switches tabs on click', async () => {
    projectStoreOverrides = {
      selected: {
        createdAt: '2025-01-01T00:00:00+00:00',
        defaultBranch: 'main',
        description: null,
        externalId: null,
        id: 'project-1',
        name: 'My Project',
        repositoryUrl: 'https://github.com/acme/my-project',
        updatedAt: '2025-01-01T00:00:00+00:00',
        visibility: 'public',
      },
    };
    const wrapper = mount(ProjectDetail);

    // Default is tech-stacks
    expect(wrapper.find('[data-testid="tech-stacks-tab"]').exists()).toBe(true);

    // Switch to dependencies
    await wrapper.find('[data-testid="tab-dependencies"]').trigger('click');
    expect(wrapper.find('[data-testid="dependencies-tab"]').exists()).toBe(true);

    // Switch to merge-requests
    await wrapper.find('[data-testid="tab-merge-requests"]').trigger('click');
    expect(wrapper.find('[data-testid="merge-requests-tab"]').exists()).toBe(true);
  });
});
