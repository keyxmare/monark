import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string) => key }),
}));

const mockLoad = vi.fn();
let dashboardOverrides: Record<string, unknown> = {};

vi.mock('@/apps/monitoring/activity/stores/dashboard', () => ({
  useDashboardStore: vi.fn(() => ({
    error: null,
    load: mockLoad,
    loading: false,
    summary: null,
    ...dashboardOverrides,
  })),
}));

import DashboardPage from '@/apps/monitoring/activity/pages/DashboardPage.vue';

describe('DashboardPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    dashboardOverrides = {};
  });

  it('renders without errors', () => {
    const wrapper = mount(DashboardPage);
    expect(wrapper.exists()).toBe(true);
    expect(wrapper.find('[data-testid="dashboard-page"]').exists()).toBe(true);
  });

  it('renders the KPI strip when loaded', () => {
    const wrapper = mount(DashboardPage);
    expect(wrapper.find('.kpi-strip').exists()).toBe(true);
  });

  it('calls load on mount', () => {
    mount(DashboardPage);
    expect(mockLoad).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    dashboardOverrides = { loading: true };
    const wrapper = mount(DashboardPage);
    expect(wrapper.find('[data-testid="dashboard-loading"]').exists()).toBe(true);
  });

  it('renders the 6 KPI tiles when loaded', () => {
    const wrapper = mount(DashboardPage);
    const kpis = wrapper.findAll('.kpi');
    expect(kpis).toHaveLength(6);
  });

  it('always renders 3 rings in the health tile', () => {
    const wrapper = mount(DashboardPage);
    const rings = wrapper.findAll('.health-ring');
    expect(rings).toHaveLength(3);
  });

  it('shows empty-notes in the remaining placeholder tiles', () => {
    const wrapper = mount(DashboardPage);
    // langs (no data), active, weak, outdated, drift are still placeholders
    const notes = wrapper.findAll('.empty-note');
    expect(notes.length).toBeGreaterThanOrEqual(5);
  });

  it('renders language rows when the dashboard summary contains languages', () => {
    dashboardOverrides = {
      summary: {
        projects: 2,
        commits30d: 0,
        activeBranches30d: 0,
        dependenciesTracked: 0,
        vulnerabilities: 0,
        coveragePercent: null,
        languages: [
          { name: 'PHP', projectsCount: 2 },
          { name: 'TypeScript', projectsCount: 1 },
        ],
        hosts: [],
      },
    };
    const wrapper = mount(DashboardPage);
    const rows = wrapper.findAll('[data-testid="dashboard-language-row"]');
    expect(rows).toHaveLength(2);
    expect(rows[0].text()).toContain('PHP');
    expect(rows[0].text()).toContain('2');
    expect(rows[1].text()).toContain('TypeScript');
    expect(rows[1].text()).toContain('1');
  });

  it('keeps the languages empty-note when the summary has no languages', () => {
    dashboardOverrides = {
      summary: {
        projects: 0,
        commits30d: 0,
        activeBranches30d: 0,
        dependenciesTracked: 0,
        vulnerabilities: 0,
        coveragePercent: null,
        languages: [],
        hosts: [],
      },
    };
    const wrapper = mount(DashboardPage);
    const tile = wrapper.find('[data-testid="dashboard-languages-tile"]');
    expect(tile.find('.empty-note').exists()).toBe(true);
    expect(tile.find('[data-testid="dashboard-language-row"]').exists()).toBe(false);
  });

  it('renders host lines reflecting the configured providers', () => {
    dashboardOverrides = {
      summary: {
        projects: 19,
        commits30d: 0,
        activeBranches30d: 0,
        dependenciesTracked: 0,
        vulnerabilities: 0,
        coveragePercent: null,
        languages: [],
        hosts: [
          { type: 'gitlab', label: 'GitLab', connected: true, projectsCount: 19 },
          { type: 'github', label: 'GitHub', connected: false, projectsCount: 0 },
        ],
      },
    };
    const wrapper = mount(DashboardPage);
    const lines = wrapper.findAll('[data-testid="dashboard-host-line"]');
    expect(lines).toHaveLength(2);
    expect(lines[0].text()).toContain('GitLab');
    expect(lines[0].text()).toContain('19');
    expect(lines[1].text()).toContain('GitHub');
    expect(lines[1].text()).toContain('0');
  });

  it('shows the hosts empty-note when no provider is configured', () => {
    dashboardOverrides = {
      summary: {
        projects: 0,
        commits30d: 0,
        activeBranches30d: 0,
        dependenciesTracked: 0,
        vulnerabilities: 0,
        coveragePercent: null,
        languages: [],
        hosts: [],
      },
    };
    const wrapper = mount(DashboardPage);
    const tile = wrapper.find('[data-testid="dashboard-hosts-tile"]');
    expect(tile.find('.empty-note').exists()).toBe(true);
    expect(tile.find('[data-testid="dashboard-host-line"]').exists()).toBe(false);
  });
});
