import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

import TechStackFilters from '@/catalog/components/TechStackFilters.vue';

describe('TechStackFilters', () => {
  const defaultProps = {
    availableFrameworks: ['Vue', 'React', 'Laravel'],
    availableLanguages: ['TypeScript', 'PHP', 'Python'],
    availableProviders: [
      { id: 'p1', name: 'GitHub' },
      { id: 'p2', name: 'GitLab' },
    ],
    search: '',
    filterFramework: '',
    filterLanguage: '',
    filterProvider: '',
    filterStatus: '',
    groupBy: 'project' as const,
    viewMode: 'frameworks' as const,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders the filters container', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    expect(wrapper.find('[data-testid="tech-stack-filters"]').exists()).toBe(true);
  });

  it('renders the search input', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    expect(wrapper.find('[data-testid="tech-stack-search"]').exists()).toBe(true);
  });

  it('renders the framework filter dropdown', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    const select = wrapper.find('[data-testid="tech-stack-filter-framework"]');
    expect(select.exists()).toBe(true);
    const options = select.findAll('option');
    // "All frameworks" + 3 frameworks
    expect(options).toHaveLength(4);
  });

  it('renders the provider filter dropdown', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    const select = wrapper.find('[data-testid="tech-stack-filter-provider"]');
    expect(select.exists()).toBe(true);
    const options = select.findAll('option');
    // "All providers" + 2 providers
    expect(options).toHaveLength(3);
  });

  it('renders the status filter dropdown', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    const select = wrapper.find('[data-testid="tech-stack-filter-status"]');
    expect(select.exists()).toBe(true);
    const options = select.findAll('option');
    // "All statuses" + active + eol + warning
    expect(options).toHaveLength(4);
  });

  it('renders group toggle buttons', () => {
    const wrapper = mount(TechStackFilters, { props: defaultProps });
    const toggle = wrapper.find('[data-testid="tech-stack-group-toggle"]');
    expect(toggle.exists()).toBe(true);
    const buttons = toggle.findAll('button');
    expect(buttons).toHaveLength(3);
  });
});
