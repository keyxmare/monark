import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ locale: { value: 'en' }, t: (key: string) => key }),
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/apps/monitoring/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    error: null,
    fetchAll: mockFetchAll,
    loading: false,
    projects: [],
    ...storeOverrides,
  })),
}));

import ProjectList from '@/apps/monitoring/catalog/pages/ProjectList.vue';

function makeProject(overrides: Record<string, unknown> = {}) {
  return {
    commitsDailySeries: Array.from({ length: 30 }, () => 0),
    commitsLast30d: 0,
    coverageJobs: [],
    coveragePercent: null,
    createdAt: '2026-01-01',
    defaultBranch: 'main',
    dependenciesCount: 0,
    description: null,
    externalId: null,
    frameworkLag: { major: 0, minor: 0, patch: 0, unknown: 0, upToDate: 0 },
    id: '1',
    lastActivityAt: null,
    lastCommitSha: null,
    name: 'Project A',
    outdatedDependenciesCount: 0,
    ownerId: 'u1',
    providerId: null,
    repositoryUrl: 'https://github.com/ex/a',
    runtimes: [],
    slug: 'project-a',
    techStacks: [],
    techStacksCount: 0,
    updatedAt: '2026-01-01',
    visibility: 'public',
    vulnerabilitiesBySeverity: { critical: 0, high: 0, low: 0, medium: 0 },
    vulnerabilitiesCount: 0,
    ...overrides,
  };
}

describe('ProjectList (repos split view)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders the repos root', () => {
    const wrapper = mount(ProjectList);
    expect(wrapper.find('[data-testid="repos-page"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount', () => {
    mount(ProjectList);
    expect(mockFetchAll).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    storeOverrides = { loading: true };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('.loading').exists()).toBe(true);
  });

  it('shows the empty state when no projects', () => {
    const wrapper = mount(ProjectList);
    expect(wrapper.find('.empty').exists()).toBe(true);
  });

  it('renders one row per project', () => {
    storeOverrides = {
      projects: [
        makeProject({ id: '1', name: 'Project A', slug: 'project-a' }),
        makeProject({
          defaultBranch: 'develop',
          id: '2',
          name: 'Project B',
          repositoryUrl: 'https://gitlab.com/ex/b',
          slug: 'project-b',
          visibility: 'private',
        }),
      ],
    };
    const wrapper = mount(ProjectList);
    const rows = wrapper.findAll('.row');
    expect(rows).toHaveLength(2);
  });

  it('opens the selected project in the detail pane', () => {
    storeOverrides = {
      projects: [
        makeProject({
          description: 'hello',
          id: '1',
          name: 'Project A',
          slug: 'project-a',
        }),
      ],
    };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('.detail-name').exists()).toBe(true);
    expect(wrapper.find('.detail-name').text()).toBe('Project A');
  });

  it('displays coverage percent when present', () => {
    storeOverrides = {
      projects: [
        makeProject({
          coveragePercent: 82.4,
          id: '1',
        }),
      ],
    };
    const wrapper = mount(ProjectList);
    const coverage = wrapper.find('.coverage-empty');
    expect(coverage.text()).toBe('82%');
  });

  it('renders the sparkline when commits are present', () => {
    storeOverrides = {
      projects: [
        makeProject({
          commitsDailySeries: [
            1, 2, 0, 3, 0, 1, 2, 0, 0, 1, 0, 0, 2, 1, 0, 0, 1, 0, 2, 0, 0, 1, 3, 0, 0, 0, 1, 0, 0,
            2,
          ],
          commitsLast30d: 20,
          id: '1',
        }),
      ],
    };
    const wrapper = mount(ProjectList);
    expect(wrapper.find('svg.spark polyline').exists()).toBe(true);
  });
});
