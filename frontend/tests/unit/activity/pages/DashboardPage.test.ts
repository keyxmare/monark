import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue-router', () => ({
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
  useRoute: vi.fn(() => ({ params: {}, query: {} })),
  useRouter: vi.fn(() => ({ push: vi.fn() })),
}));

vi.mock('vue-i18n', () => ({
  useI18n: () => ({ t: (key: string, params?: Record<string, unknown>) => key }),
}));

vi.mock('@/shared/layouts/DashboardLayout.vue', () => ({
  default: { template: '<div><slot /></div>' },
}));

const mockLoad = vi.fn();
let dashboardOverrides: Record<string, unknown> = {};

vi.mock('@/activity/stores/dashboard', () => ({
  useDashboardStore: vi.fn(() => ({
    error: null,
    load: mockLoad,
    loading: false,
    metrics: [],
    ...dashboardOverrides,
  })),
}));

import DashboardPage from '@/activity/pages/DashboardPage.vue';

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

  it('shows the dashboard title', () => {
    const wrapper = mount(DashboardPage);
    expect(wrapper.find('[data-testid="dashboard-title"]').exists()).toBe(true);
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

  it('renders metric cards when loaded', () => {
    dashboardOverrides = {
      metrics: [
        { label: 'Projects', value: 12, change: 5 },
        { label: 'Dependencies', value: 150, change: -2 },
      ],
    };
    const wrapper = mount(DashboardPage);
    const cards = wrapper.findAll('[data-testid="metric-card"]');
    expect(cards).toHaveLength(2);
  });
});
