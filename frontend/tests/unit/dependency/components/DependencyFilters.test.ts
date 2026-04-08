import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

let projectStoreOverrides: Record<string, unknown> = {};

vi.mock('@/catalog/stores/project', () => ({
  useProjectStore: vi.fn(() => ({
    projects: [],
    ...projectStoreOverrides,
  })),
}));

import DependencyFilters from '@/dependency/components/DependencyFilters.vue';

describe('DependencyFilters', () => {
  const defaultProps = {
    search: '',
    filterPm: '',
    filterType: '',
    filterStatus: '',
    filterProject: '',
  };

  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    projectStoreOverrides = {};
  });

  it('renders the search input', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const input = wrapper.find('input[type="search"]');
    expect(input.exists()).toBe(true);
  });

  it('renders all select dropdowns', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const selects = wrapper.findAll('select');
    // filterPm, filterType, filterStatus, filterProject
    expect(selects).toHaveLength(4);
  });

  it('renders package manager options', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const pmSelect = wrapper.findAll('select')[0];
    const options = pmSelect.findAll('option');
    // All + Composer + npm + pip
    expect(options).toHaveLength(4);
  });

  it('renders type options', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const typeSelect = wrapper.findAll('select')[1];
    const options = typeSelect.findAll('option');
    // All + runtime + dev
    expect(options).toHaveLength(3);
  });

  it('renders status options', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const statusSelect = wrapper.findAll('select')[2];
    const options = statusSelect.findAll('option');
    // All + outdated + uptodate
    expect(options).toHaveLength(3);
  });

  it('renders project options from store', () => {
    projectStoreOverrides = {
      projects: [
        { id: 'p1', name: 'Project A' },
        { id: 'p2', name: 'Project B' },
      ],
    };
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const projectSelect = wrapper.findAll('select')[3];
    const options = projectSelect.findAll('option');
    // All + 2 projects
    expect(options).toHaveLength(3);
  });

  it('renders empty project list when no projects', () => {
    const wrapper = mount(DependencyFilters, { props: defaultProps });
    const projectSelect = wrapper.findAll('select')[3];
    const options = projectSelect.findAll('option');
    // Just the "All" option
    expect(options).toHaveLength(1);
  });
});
