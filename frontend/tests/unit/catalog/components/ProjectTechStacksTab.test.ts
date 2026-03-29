import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createTechStack } from '../../../factories';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

vi.mock('@/catalog/composables/useFrameworkLts', () => ({
  humanizeTimeDiff: () => '6 months',
  isVersionUpToDate: () => false,
  ltsUrgency: () => 'moderate',
  patchGap: () => null,
  useFrameworkLts: () => ({
    getLtsInfo: () => null,
    getVersionMaintenanceStatus: () => null,
    getVersionReleaseDate: () => null,
    loadForFrameworks: vi.fn().mockResolvedValue(undefined),
  }),
}));

const mockFetchAll = vi.fn();
let storeOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/tech-stack', () => ({
  useTechStackStore: vi.fn(() => ({
    currentPage: 1,
    fetchAll: mockFetchAll,
    loading: false,
    techStacks: [],
    total: 0,
    totalPages: 0,
    ...storeOverrides,
  })),
}));

import ProjectTechStacksTab from '@/catalog/components/ProjectTechStacksTab.vue';

describe('ProjectTechStacksTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    storeOverrides = {};
  });

  it('renders the tech stacks panel', () => {
    const wrapper = mount(ProjectTechStacksTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="tech-stacks-panel"]').exists()).toBe(true);
  });

  it('calls fetchAll on mount with projectId', () => {
    mount(ProjectTechStacksTab, { props: { projectId: 'project-1' } });
    expect(mockFetchAll).toHaveBeenCalledWith(1, 20, 'project-1');
  });

  it('shows empty state when no tech stacks', () => {
    const wrapper = mount(ProjectTechStacksTab, { props: { projectId: 'project-1' } });
    expect(wrapper.find('[data-testid="tech-stacks-empty"]').exists()).toBe(true);
  });

  it('renders tech stack rows when data exists', () => {
    storeOverrides = {
      techStacks: [
        createTechStack({ id: 'ts-1', language: 'TypeScript', framework: 'Vue' }),
        createTechStack({ id: 'ts-2', language: 'PHP', framework: 'Laravel' }),
      ],
    };
    const wrapper = mount(ProjectTechStacksTab, { props: { projectId: 'project-1' } });
    const rows = wrapper.findAll('[data-testid="tech-stack-row"]');
    expect(rows).toHaveLength(2);
  });

  it('displays language and framework in rows', () => {
    storeOverrides = {
      techStacks: [createTechStack({ language: 'TypeScript', framework: 'Vue' })],
    };
    const wrapper = mount(ProjectTechStacksTab, { props: { projectId: 'project-1' } });
    const row = wrapper.find('[data-testid="tech-stack-row"]');
    expect(row.text()).toContain('TypeScript');
    expect(row.text()).toContain('Vue');
  });
});
