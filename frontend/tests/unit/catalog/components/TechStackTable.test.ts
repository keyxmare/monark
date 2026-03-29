import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
}));

vi.mock('@/shared/components/TechBadge.vue', () => ({
  default: {
    props: ['name', 'version', 'size'],
    template: '<span>{{ name }} {{ version }}</span>',
  },
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
  }),
}));

import type { GroupedStack } from '@/catalog/composables/useTechStackGrouping';

import TechStackTable from '@/catalog/components/TechStackTable.vue';

function createGroupedStack(overrides?: Partial<GroupedStack>): GroupedStack {
  return {
    groupIndex: 0,
    groupSize: 1,
    isFirstInGroup: true,
    projectId: 'project-1',
    projectName: 'My Project',
    ts: {
      id: 'stack-1',
      language: 'TypeScript',
      framework: 'Vue',
      version: '5.7',
      frameworkVersion: '3.5',
      detectedAt: '2025-01-01T00:00:00+00:00',
      projectId: 'project-1',
      createdAt: '2025-01-01T00:00:00+00:00',
    },
    ...overrides,
  };
}

describe('TechStackTable', () => {
  const defaultProps = {
    groupBy: 'project' as const,
    groupedStacks: [createGroupedStack()],
    sortIndicator: () => '',
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the table', () => {
    const wrapper = mount(TechStackTable, { props: defaultProps });
    expect(wrapper.find('[data-testid="tech-stack-list-table"]').exists()).toBe(true);
  });

  it('renders rows for each grouped stack', () => {
    const wrapper = mount(TechStackTable, {
      props: {
        ...defaultProps,
        groupedStacks: [
          createGroupedStack({ ts: { ...createGroupedStack().ts, id: 'stack-1' } }),
          createGroupedStack({
            ts: { ...createGroupedStack().ts, id: 'stack-2', language: 'PHP' },
            isFirstInGroup: false,
          }),
        ],
      },
    });
    const rows = wrapper.findAll('[data-testid="tech-stack-list-row"]');
    expect(rows).toHaveLength(2);
  });

  it('shows empty state when no stacks', () => {
    const wrapper = mount(TechStackTable, {
      props: { ...defaultProps, groupedStacks: [] },
    });
    expect(wrapper.find('[data-testid="tech-stack-list-no-match"]').exists()).toBe(true);
  });

  it('emits sort when column header is clicked', async () => {
    const wrapper = mount(TechStackTable, { props: defaultProps });
    const headers = wrapper.findAll('th');
    // First th is "project"
    await headers[0].trigger('click');
    expect(wrapper.emitted('sort')).toBeTruthy();
    expect(wrapper.emitted('sort')![0]).toEqual(['project']);
  });

  it('displays project name in first column', () => {
    const wrapper = mount(TechStackTable, { props: defaultProps });
    const row = wrapper.find('[data-testid="tech-stack-list-row"]');
    expect(row.text()).toContain('My Project');
  });
});
