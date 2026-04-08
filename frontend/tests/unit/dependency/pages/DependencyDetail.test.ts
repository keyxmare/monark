import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: { id: 'dep-1' }, query: {} })),
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

const mockDependencyFetchOne = vi.fn();
let dependencyStoreOverrides: Record<string, unknown> = {};

vi.mock('@/dependency/stores/dependency', () => ({
  useDependencyStore: vi.fn(() => ({
    error: null,
    fetchOne: mockDependencyFetchOne,
    loading: false,
    selectedDependency: null,
    ...dependencyStoreOverrides,
  })),
}));

const mockProjectFetchOne = vi.fn();
let projectStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    fetchOne: mockProjectFetchOne,
    selected: null,
    ...projectStoreOverrides,
  })),
}));

const mockVulnFetchAll = vi.fn();
let vulnStoreOverrides: Record<string, unknown> = {};

vi.mock('@/dependency/stores/vulnerability', () => ({
  useVulnerabilityStore: vi.fn(() => ({
    fetchAll: mockVulnFetchAll,
    vulnerabilities: [],
    ...vulnStoreOverrides,
  })),
}));

import DependencyDetail from '@/dependency/pages/DependencyDetail.vue';

describe('DependencyDetail', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    dependencyStoreOverrides = {};
    projectStoreOverrides = {};
    vulnStoreOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(DependencyDetail);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-detail-page"]').exists()).toBe(true);
  });

  it('calls fetchOne on mount', () => {
    mount(DependencyDetail);
    expect(mockDependencyFetchOne).toHaveBeenCalledWith('dep-1');
  });

  it('shows loading state', () => {
    dependencyStoreOverrides = { loading: true };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-loading"]').exists()).toBe(true);
  });

  it('shows error state', () => {
    dependencyStoreOverrides = { error: 'Not found' };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-detail-error"]').text()).toContain('Not found');
  });

  it('renders dependency detail card when data exists', () => {
    dependencyStoreOverrides = {
      selectedDependency: {
        createdAt: '2025-01-01T00:00:00+00:00',
        currentVersion: '3.4.0',
        id: 'dep-1',
        isOutdated: true,
        latestVersion: '3.5.0',
        ltsVersion: '3.4.0',
        name: 'vue',
        packageManager: 'npm',
        projectId: 'project-1',
        repositoryUrl: 'https://github.com/vuejs/core',
        type: 'runtime',
        vulnerabilityCount: 2,
      },
    };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="dependency-detail-current-version"]').text()).toContain(
      '3.4.0',
    );
    expect(wrapper.find('[data-testid="dependency-detail-latest-version"]').text()).toContain(
      '3.5.0',
    );
    expect(wrapper.find('[data-testid="dependency-detail-lts-version"]').text()).toContain('3.4.0');
    expect(wrapper.find('[data-testid="dependency-detail-package-manager"]').text()).toContain(
      'npm',
    );
    expect(wrapper.find('[data-testid="dependency-detail-type"]').text()).toContain('runtime');
    expect(wrapper.find('[data-testid="dependency-detail-vuln-count"]').text()).toContain('2');
  });

  it('renders outdated badge when dependency is outdated', () => {
    dependencyStoreOverrides = {
      selectedDependency: {
        createdAt: '2025-01-01T00:00:00+00:00',
        currentVersion: '3.4.0',
        id: 'dep-1',
        isOutdated: true,
        latestVersion: '3.5.0',
        ltsVersion: '3.4.0',
        name: 'vue',
        packageManager: 'npm',
        projectId: 'project-1',
        repositoryUrl: null,
        type: 'runtime',
        vulnerabilityCount: 0,
      },
    };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-outdated"]').exists()).toBe(true);
  });

  it('renders repository URL when present', () => {
    dependencyStoreOverrides = {
      selectedDependency: {
        createdAt: '2025-01-01T00:00:00+00:00',
        currentVersion: '3.4.0',
        id: 'dep-1',
        isOutdated: false,
        latestVersion: '3.5.0',
        ltsVersion: '3.4.0',
        name: 'vue',
        packageManager: 'npm',
        projectId: 'project-1',
        repositoryUrl: 'https://github.com/vuejs/core',
        type: 'runtime',
        vulnerabilityCount: 0,
      },
    };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-repository-url"]').exists()).toBe(true);
  });

  it('shows project link when project is loaded', () => {
    dependencyStoreOverrides = {
      selectedDependency: {
        createdAt: '2025-01-01T00:00:00+00:00',
        currentVersion: '3.4.0',
        id: 'dep-1',
        isOutdated: false,
        latestVersion: '3.5.0',
        ltsVersion: '3.4.0',
        name: 'vue',
        packageManager: 'npm',
        projectId: 'project-1',
        repositoryUrl: null,
        type: 'runtime',
        vulnerabilityCount: 0,
      },
    };
    projectStoreOverrides = {
      selected: { id: 'project-1', name: 'My Project' },
    };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('[data-testid="dependency-detail-project"]').exists()).toBe(true);
  });

  it('renders vulnerability table when linked vulnerabilities exist', () => {
    dependencyStoreOverrides = {
      selectedDependency: {
        createdAt: '2025-01-01T00:00:00+00:00',
        currentVersion: '3.4.0',
        id: 'dep-1',
        isOutdated: false,
        latestVersion: '3.5.0',
        ltsVersion: '3.4.0',
        name: 'vue',
        packageManager: 'npm',
        projectId: 'project-1',
        repositoryUrl: null,
        type: 'runtime',
        vulnerabilityCount: 1,
      },
    };
    vulnStoreOverrides = {
      vulnerabilities: [
        {
          cveId: 'CVE-2025-0001',
          dependencyId: 'dep-1',
          id: 'vuln-1',
          severity: 'high',
          status: 'open',
          title: 'Prototype Pollution',
        },
      ],
    };
    const wrapper = mount(DependencyDetail);
    expect(wrapper.find('table').exists()).toBe(true);
  });
});
