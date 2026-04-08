import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { RemoteProject } from '@/catalog/types/provider';

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string, params?: Record<string, unknown>) => key }),
}));

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
}));

vi.mock('@/shared/components/Pagination.vue', () => ({
  default: { template: '<div />' },
}));

import RemoteProjectsSection from '@/catalog/components/RemoteProjectsSection.vue';

function createRemoteProject(overrides?: Partial<RemoteProject>): RemoteProject {
  return {
    externalId: 'ext-1',
    name: 'Remote Project',
    slug: 'remote-project',
    description: 'A remote project',
    repositoryUrl: 'https://github.com/acme/remote',
    defaultBranch: 'main',
    visibility: 'public',
    avatarUrl: null,
    alreadyImported: false,
    localProjectId: null,
    ...overrides,
  };
}

describe('RemoteProjectsSection', () => {
  const defaultProps = {
    error: null,
    importing: false,
    initialLoaded: true,
    projects: [] as RemoteProject[],
    remoteProjectsCurrentPage: 1,
    remoteProjectsTotalPages: 1,
    syncing: false,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('shows loading state when not initially loaded', () => {
    const wrapper = mount(RemoteProjectsSection, {
      props: { ...defaultProps, initialLoaded: false },
    });
    expect(wrapper.find('[data-testid="remote-projects-loading"]').exists()).toBe(true);
  });

  it('shows empty state when no projects', () => {
    const wrapper = mount(RemoteProjectsSection, { props: defaultProps });
    expect(wrapper.find('[data-testid="remote-projects-empty"]').exists()).toBe(true);
  });

  it('renders project cards when projects exist', () => {
    const projects = [
      createRemoteProject({ externalId: 'ext-1', name: 'Project A' }),
      createRemoteProject({ externalId: 'ext-2', name: 'Project B' }),
    ];
    const wrapper = mount(RemoteProjectsSection, { props: { ...defaultProps, projects } });
    const cards = wrapper.findAll('[data-testid="remote-project-card"]');
    expect(cards).toHaveLength(2);
  });

  it('shows error message when error prop is set', () => {
    const wrapper = mount(RemoteProjectsSection, {
      props: { ...defaultProps, error: 'API rate limit exceeded' },
    });
    expect(wrapper.find('[data-testid="remote-projects-error"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="remote-projects-error"]').text()).toContain(
      'API rate limit exceeded',
    );
  });

  it('renders sync all button', () => {
    const wrapper = mount(RemoteProjectsSection, { props: defaultProps });
    const btn = wrapper.find('[data-testid="provider-sync-all"]');
    expect(btn.exists()).toBe(true);
  });

  it('emits syncAll when sync button is clicked', async () => {
    const wrapper = mount(RemoteProjectsSection, { props: defaultProps });
    await wrapper.find('[data-testid="provider-sync-all"]').trigger('click');
    expect(wrapper.emitted('syncAll')).toHaveLength(1);
  });

  it('renders imported badge for already imported projects', () => {
    const projects = [createRemoteProject({ alreadyImported: true, localProjectId: 'local-1' })];
    const wrapper = mount(RemoteProjectsSection, { props: { ...defaultProps, projects } });
    expect(wrapper.find('[data-testid="remote-project-imported-badge"]').exists()).toBe(true);
  });
});
